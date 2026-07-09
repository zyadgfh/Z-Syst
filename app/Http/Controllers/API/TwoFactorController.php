<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\Auth\TwoFactorSetupRequest;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TwoFactorController extends BaseController
{
    public function __construct(
        private AuthService $authService,
        private TwoFactorService $twoFactorService
    ) {}

    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success($this->twoFactorService->generateSetup($user));
    }

    public function confirm(TwoFactorSetupRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->twoFactorService->confirm($user, $request->validated()['two_factor_code']);

        return $this->success($result, 'Two-factor authentication enabled successfully');
    }

    public function disable(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
            'two_factor_code' => ['nullable', 'string', 'size:6'],
        ]);

        $this->twoFactorService->disable(
            $request->user(),
            $validated['password'],
            $validated['two_factor_code'] ?? null
        );

        return $this->success(null, 'Two-factor authentication disabled successfully');
    }
}
