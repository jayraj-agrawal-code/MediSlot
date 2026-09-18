<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\LoginRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AdminAuthService $adminAuthService) {}

    /**
     * Log an admin in and start a stateful session.
     */
    public function store(LoginRequest $request): AdminResource
    {
        $admin = $this->adminAuthService->login(
            $request->validated(),
            $request->throttleKey(),
            $request,
        );

        return AdminResource::make($admin);
    }

    /**
     * Log the currently authenticated admin out.
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->adminAuthService->logout($request);

        return response()->json(status: 204);
    }
}
