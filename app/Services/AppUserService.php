<?php

namespace App\Services;

use App\Models\AppUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AppUserService extends Service
{
    public function login(string $deviceId): ?AppUser
    {
        return AppUser::where('device_id', $deviceId)->first();
    }

    public function createToken(AppUser $appUser, string $tokenName = 'app-user-token'): string
    {
        return $appUser->createToken($tokenName)->plainTextToken;
    }

    public function createOrGetByDeviceId(string $deviceId, ?Request $request = null, array $data = []): AppUser
    {
        $appUser = AppUser::where('device_id', $deviceId)->first();

        if (!$appUser) {
            // Collect request metadata
            $meta = $data['meta'] ?? [];
            
            if ($request) {
                $meta = array_merge($meta, [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'accept_language' => $request->header('Accept-Language'),
                    'accept_encoding' => $request->header('Accept-Encoding'),
                    'referer' => $request->header('Referer'),
                    'origin' => $request->header('Origin'),
                    'host' => $request->header('Host'),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'created_at' => now()->toIso8601String(),
                ]);
            }

            // Merge data but ensure our meta takes precedence
            $createData = $data;
            unset($createData['meta']); // Remove meta from $data if it exists
            $createData = array_merge($createData, [
                'device_id' => $deviceId,
                'meta' => $meta,
            ]);
            $appUser = AppUser::create($createData);
        } else {
            // Update existing user's meta with new request info if provided
            if ($request) {
                $existingMeta = $appUser->meta ?? [];
                $newMeta = array_merge($existingMeta, [
                    'last_login_ip' => $request->ip(),
                    'last_login_user_agent' => $request->userAgent(),
                    'last_login_at' => now()->toIso8601String(),
                ]);
                $appUser->update(['meta' => $newMeta]);
            }
        }

        return $appUser;
    }

    public function getAll(array $filters = [], string $sortBy = 'created_at', string $sortDir = 'desc', int $perPage = 15)
    {
        $query = AppUser::with(['payments.invoice.merchant']);

        // Apply filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('device_id', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply sorting
        $allowedSorts = ['name', 'email', 'created_at', 'updated_at', 'device_id'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'created_at';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? strtolower($sortDir) : 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Apply pagination
        return $query->paginate($perPage)->withQueryString();
    }

    public function getById(int $id): ?AppUser
    {
        return AppUser::with(['payments.invoice.merchant', 'payments.invoice.consumer'])
            ->find($id);
    }

    public function getInstallationStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $installations = AppUser::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Create a complete date range
        $dateRange = [];
        $stats = [];
        
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dateLabel = now()->subDays($i)->format('M d');
            $dateRange[] = $dateLabel;
            $stats[$date] = 0;
        }

        // Fill in actual data
        foreach ($installations as $installation) {
            $date = $installation->date;
            if (isset($stats[$date])) {
                $stats[$date] = $installation->count;
            }
        }

        return [
            'labels' => $dateRange,
            'data' => array_values($stats),
            'total' => AppUser::count(),
        ];
    }
}

