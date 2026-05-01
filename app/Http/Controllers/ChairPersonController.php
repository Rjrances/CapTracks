<?php
namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\Notification;
use App\Services\NotificationService;

class ChairpersonController extends Controller
{
    public function getActiveTerm()
    {
        return AcademicTerm::where('is_active', true)->first();
    }

    public function notifications()
    {
        $notifications = Notification::where('role', 'chairperson')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('chairperson.notifications', compact('notifications'));
    }

    public function markNotificationAsRead($notificationId)
    {
        $notification = Notification::findOrFail($notificationId);
        
        if ($notification->role !== 'chairperson') {
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
        $notificationIds = Notification::where('role', 'chairperson')
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
        $notification = Notification::findOrFail($notificationId);
        
        if ($notification->role !== 'chairperson') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
    }
}
