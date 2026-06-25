<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AppUser;
use App\Services\AppUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        protected AppUserService $appUserService
    ) {
    }

    public function loginWithDeviceId(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $appUser = $this->appUserService->login($request->input('device_id'));

        if (!$appUser) {
            // Create new app user if doesn't exist
            $appUser = $this->appUserService->createOrGetByDeviceId($request->input('device_id'), $request);
        } else {
            // Update existing user's meta with new request info
            $appUser = $this->appUserService->createOrGetByDeviceId($request->input('device_id'), $request);
        }

        $token = $this->appUserService->createToken($appUser);

        return (new UserResource($appUser))->additional([
            'token' => $token,
        ])->response();
    }

    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bearer_token' => 'required|string',
            'mobile_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implement signup logic with bearer token verification
        // For now, create/update app user with mobile number
        $appUser = AppUser::where('device_id', $request->input('bearer_token'))->first();
        
        if (!$appUser) {
            return response()->json([
                'message' => 'Invalid bearer token',
            ], 401);
        }

        // Update mobile number in meta
        $meta = $appUser->meta ?? [];
        $meta['mobile_number'] = $request->input('mobile_number');
        $appUser->update(['meta' => $meta]);

        $token = $this->appUserService->createToken($appUser);

        return (new UserResource($appUser->fresh()))->additional([
            'token' => $token,
        ])->response();
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mobile_number' => 'required|string',
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implement OTP verification logic
        // For now, return true (mock verification)
        return response()->json([
            'verified' => true,
        ]);
    }

    public function confirmDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'passcode' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get user from token
        $appUser = $request->user();
        
        if (!$appUser) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Update user details from request
        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->input('name');
        }
        if ($request->has('email')) {
            $updateData['email'] = $request->input('email');
        }
        if ($request->has('password')) {
            $updateData['password'] = bcrypt($request->input('password'));
        }
        
        // Update meta with any additional details
        $meta = $appUser->meta ?? [];
        foreach ($request->except(['passcode', 'password', 'name', 'email']) as $key => $value) {
            $meta[$key] = $value;
        }
        $updateData['meta'] = $meta;

        $appUser->update($updateData);

        $token = $this->appUserService->createToken($appUser->fresh());

        return (new UserResource($appUser->fresh()))->additional([
            'token' => $token,
        ])->response();
    }
}

