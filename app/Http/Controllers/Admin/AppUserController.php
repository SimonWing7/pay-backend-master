<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppUserService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppUserController extends Controller
{
    public function __construct(
        protected AppUserService $appUserService
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->get('search'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $perPage = $request->get('per_page', 15);

        $appUsers = $this->appUserService->getAll($filters, $sortBy, $sortDir, $perPage);
        return view('admin.app_users.index', compact('appUsers'));
    }

    public function show(int $id): View
    {
        $appUser = $this->appUserService->getById($id);

        if (!$appUser) {
            abort(404, 'App User not found');
        }

        return view('admin.app_users.show', compact('appUser'));
    }
}

