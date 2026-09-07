<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * POST /api/v1/auth/register
     * Placeholder — full auth in a later module.
     */
    public function register(Request $request): JsonResponse
    {
        return $this->successResponse(
            null,
            'Auth register endpoint placeholder. Coming in a later module.',
            200
        );
    }

    /**
     * POST /api/v1/auth/login
     * Placeholder — full auth in a later module.
     */
    public function login(Request $request): JsonResponse
    {
        return $this->successResponse(
            null,
            'Auth login endpoint placeholder. Coming in a later module.',
            200
        );
    }

    /**
     * POST /api/v1/auth/logout
     * Placeholder — full auth in a later module.
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->successResponse(
            null,
            'Auth logout endpoint placeholder. Coming in a later module.',
            200
        );
    }

    /**
     * GET /api/v1/auth/me
     * Placeholder — full auth in a later module.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            null,
            'Auth me endpoint placeholder. Coming in a later module.',
            200
        );
    }
}
