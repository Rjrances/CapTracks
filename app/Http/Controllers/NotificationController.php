<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    /**
     * Mark a notification as read
     */
    public function markAsRead(Request $request, Notification $notification)
    {
        try {
            // Check if user has access to this notification
            $userRole = null;
            
            if (auth()->check()) {
                // Authenticated user (faculty/staff)
                $userRole = auth()->user()->getPrimaryRoleAttribute();
            } elseif (session('is_student') && session('student_id')) {
                // Session-based student
                $userRole = 'student';
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            // Check if user has access to this notification (either by role or specific user_id)
            $userId = null;
            if (auth()->check()) {
                $userId = auth()->user()->id;
            } elseif (session('is_student') && session('student_id')) {
                $userId = session('student_id');
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

    /**
     * Mark multiple notifications as read
     */
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

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $userRole = null;
            $userId = null;
            
            if (auth()->check()) {
                // Authenticated user (faculty/staff)
                $user = auth()->user();
                $userRole = $user->getPrimaryRoleAttribute();
                $userId = $user->id;
            } elseif (session('is_student') && session('student_id')) {
                // Session-based student
                $userRole = 'student';
                $userId = session('student_id');
            } else {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            
            // Get notifications that match either the user's role OR are specifically for this user
            $notificationIds = Notification::where(function($query) use ($userRole, $userId) {
                $query->where('role', $userRole)
                      ->orWhere('user_id', $userId);
            })
            ->where('is_read', false)
            ->pluck('id')
            ->toArray();

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
                'user_role' => $userRole ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['success' => false, 'message' => 'Error updating notifications: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        try {
            // Check if user has access to this notification
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

    /**
     * Delete multiple notifications
     */
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

    /**
     * Get notifications for a specific role with filters
     */
    public function getNotifications(Request $request)
    {
        try {
            $userRole = auth()->user()->getPrimaryRoleAttribute();
            
            $query = Notification::where('role', $userRole);

            // Apply filters
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
