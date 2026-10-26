<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Notification::where('user_id', $user->id); // IDOR protection

        if ($request->boolean('unread')) {
            $query->unread();
        }

        if ($request->has('type')) {
            $query->byType($request->query('type'));
        }

        $notifications = $query->orderBy('is_read')
            ->orderByDesc('created_at')
            ->get();

        $unreadCount = Notification::where('user_id', $user->id)->unread()->count();

        return $this->successResponse([
            'unread_count' => $unreadCount,
            'notifications' => $notifications,
        ], 'Notifications retrieved.');
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $n = Notification::where('user_id', $request->user()->id)->find($id);

        if (! $n) {
            return $this->notFoundResponse('Notification');
        }

        return $this->successResponse($n, 'Notification retrieved.');
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $n = Notification::where('user_id', $request->user()->id)->find($id);

        if (! $n) {
            return $this->notFoundResponse('Notification');
        }

        $n->markAsRead();

        return $this->successResponse($n->fresh(), 'Notification marked as read.');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $n = Notification::where('user_id', $request->user()->id)->find($id);

        if (! $n) {
            return $this->notFoundResponse('Notification');
        }

        $n->delete();

        return $this->successResponse(null, 'Notification deleted.');
    }
}
