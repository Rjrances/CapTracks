<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
class NotificationController extends Controller
{
    public function markAsRead(Request $request, Notification $notification)
    {
        try {
            $userRole = null;
            if (auth()->check()) {
                $userRole = auth()->user()->getPrimaryRoleAttribute();
            } elseif (Auth::guard('student')->check()) {
                $userRole = 'student';
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $userId = null;
            if (auth()->check()) {
                $userId = auth()->user()->id;
            } elseif (Auth::guard('student')->check()) {
                $userId = Auth::guard('student')->id();
            }
            if ($notification->role !== $userRole && $notification->user_id !== $userId) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $success = NotificationService::markAsRead($notification->id);
            if ($success) {
                return response()->json(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                return response()->json(['success' => false, 'message' => 'Error updating notification'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error marking notification as read', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Error updating notification'], 500);
        }
    }
    public function markMultipleAsRead(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:notifications,id'
            ]);
            $success = NotificationService::markMultipleAsRead($request->notification_ids);
            if ($success) {
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => 'Error updating notifications'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error marking multiple notifications as read', [
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Error updating notifications'], 500);
        }
    }
    public function markAllAsRead(Request $request)
    {
        try {
            if (auth()->check()) {
                $user = auth()->user();
                $notificationIds = Notification::query()
                    ->visibleToWebUser($user)
                    ->where('is_read', false)
                    ->pluck('id')
                    ->toArray();
            } elseif (Auth::guard('student')->check()) {
                $studentAccount = Auth::guard('student')->user();
                $student = $studentAccount?->student;
                if (!$student) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
                $notificationIds = Notification::query()
                    ->visibleToStudent($student, $studentAccount->id)
                    ->where('is_read', false)
                    ->pluck('id')
                    ->toArray();
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            if (empty($notificationIds)) {
                return response()->json(['success' => true, 'message' => 'No unread notifications found for your role']);
            }
            $success = NotificationService::markMultipleAsRead($notificationIds);
            if ($success) {
                return response()->json(['success' => true, 'message' => count($notificationIds) . ' notifications marked as read']);
            } else {
                return response()->json(['success' => false, 'message' => 'Error updating notifications'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Error updating notifications: ' . $e->getMessage()], 500);
        }
    }
    public function destroy(Notification $notification)
    {
        try {
            if ($notification->role !== auth()->user()->getPrimaryRoleAttribute()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            $success = NotificationService::deleteNotification($notification->id);
            if ($success) {
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => 'Error deleting notification'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting notification', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Error deleting notification'], 500);
        }
    }
    public function deleteMultiple(Request $request)
    {
        try {
            $request->validate([
                'notification_ids' => 'required|array',
                'notification_ids.*' => 'integer|exists:notifications,id'
            ]);
            $success = NotificationService::deleteMultipleNotifications($request->notification_ids);
            if ($success) {
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => 'Error deleting notifications'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error deleting multiple notifications', [
                'notification_ids' => $request->notification_ids,
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Error deleting notifications'], 500);
        }
    }
    public function getNotifications(Request $request)
    {
        try {
            $query = null;

            if (auth()->check()) {
                $query = Notification::query()->visibleToWebUser(auth()->user());
            } elseif (Auth::guard('student')->check()) {
                $studentAccount = Auth::guard('student')->user();
                $student = $studentAccount?->student;
                if (!$student) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
                $query = Notification::query()->visibleToStudent($student, $studentAccount->id);
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($request->filled('status')) {
                if ($request->status === 'unread') {
                    $query->where('is_read', false);
                } elseif ($request->status === 'read') {
                    $query->where('is_read', true);
                }
            }
            if ($request->filled('date')) {
                $now = now();
                switch ($request->date) {
                    case 'today':
                        $query->whereDate('created_at', $now->toDateString());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
                        break;
                }
            }
            $notifications = $query->orderBy('created_at', 'desc')->get();
            return response()->json([
                'success' => true,
                'notifications' => $notifications
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting notifications', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            return response()->json(['success' => false, 'message' => 'Error retrieving notifications'], 500);
        }
    }
}
