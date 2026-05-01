<?php
namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ChairpersonController extends Controller
{
    public function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    public function notifications()
    {
        $user = auth()->user();
        $notifications = Notification::query()
            ->visibleToWebUser($user)
            ->latest()
            ->get();

        return view('chairperson.notifications', compact('notifications'));
    }

    public function markNotificationAsRead($notificationId)
    {
        $user = auth()->user();
        $notification = Notification::findOrFail($notificationId);
        
        $hasAccess = Notification::query()
            ->visibleToWebUser($user)
            ->whereKey($notification->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $success = NotificationService::markAsRead($notificationId);

        if ($success) {
            return response()->json(['success' => true, 'message' => 'Notification marked as read']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error updating notification'], 500);
        }
    }

    public function markAllNotificationsAsRead()
    {
        $user = auth()->user();
        $notificationIds = Notification::query()
            ->visibleToWebUser($user)
            ->where('is_read', false)
            ->pluck('id')
            ->toArray();

        if (empty($notificationIds)) {
            return response()->json(['success' => true, 'message' => 'No unread notifications found']);
        }

        $success = NotificationService::markMultipleAsRead($notificationIds);

        if ($success) {
            return response()->json(['success' => true, 'message' => count($notificationIds) . ' notifications marked as read']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error updating notifications'], 500);
        }
    }

    public function deleteNotification($notificationId)
    {
        $user = auth()->user();
        $notification = Notification::findOrFail($notificationId);
        
        $hasAccess = Notification::query()
            ->visibleToWebUser($user)
            ->whereKey($notification->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
    }

    public function markMultipleAsRead(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        $updated = Notification::whereIn('id', $request->notification_ids)
            ->visibleToWebUser($user)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => $updated . ' notifications marked as read'
        ]);
    }

    public function deleteMultiple(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        $deleted = Notification::whereIn('id', $request->notification_ids)
            ->visibleToWebUser($user)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . ' notifications deleted'
        ]);
    }
}
