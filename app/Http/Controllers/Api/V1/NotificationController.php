<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    public function index(): JsonResponse
    {
        return $this->successResponse([], 'Notifications retrieved successfully.');
    }

    public function markAsRead(string $id): JsonResponse
    {
        return $this->successResponse(
            ['id' => (int) $id],
            'Notification marked as read.'
        );
    }
}
