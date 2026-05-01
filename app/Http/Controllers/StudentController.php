<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\Group;
use App\Models\AcademicTerm;
use App\Models\Notification;
use App\Models\Offering;
use App\Services\NotificationService;

class StudentController extends Controller
{
    public function index()
    {
        return redirect()->route('student.dashboard');
    }

    private function getAuthenticatedStudent()
    {
        if (Auth::guard('student')->check()) {
            $studentAccount = Auth::guard('student')->user();
            return $studentAccount->student;
        }
        return null;
    }

    public function notifications()
    {
        $student = $this->getAuthenticatedStudent();
        $studentAccountId = Auth::guard('student')->id();
        
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Student not found.');
        }

        $notifications = Notification::query()
            ->visibleToStudent($student, $studentAccountId)
            ->latest()
            ->get();

        return view('student.notifications', compact('notifications'));
    }

    public function markNotificationAsRead($notificationId)
    {
        $student = $this->getAuthenticatedStudent();
        $studentAccountId = Auth::guard('student')->id();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification = Notification::findOrFail($notificationId);
        
        $hasAccess = Notification::query()
            ->visibleToStudent($student, $studentAccountId)
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
        $student = $this->getAuthenticatedStudent();
        $studentAccountId = Auth::guard('student')->id();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notificationIds = Notification::query()
            ->visibleToStudent($student, $studentAccountId)
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
        $student = $this->getAuthenticatedStudent();
        $studentAccountId = Auth::guard('student')->id();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification = Notification::findOrFail($notificationId);
        
        $hasAccess = Notification::query()
            ->visibleToStudent($student, $studentAccountId)
            ->whereKey($notification->id)
            ->exists();

        if (!$hasAccess) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification deleted successfully']);
    }

    public function markMultipleAsRead(Request $request)
    {
        $student = $this->getAuthenticatedStudent();
        $studentAccountId = Auth::guard('student')->id();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        $updated = Notification::whereIn('id', $request->notification_ids)
            ->visibleToStudent($student, $studentAccountId)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => $updated . ' notifications marked as read'
        ]);
    }

    public function deleteMultiple(Request $request)
    {
        $student = $this->getAuthenticatedStudent();
        $studentAccountId = Auth::guard('student')->id();
        
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        $deleted = Notification::whereIn('id', $request->notification_ids)
            ->visibleToStudent($student, $studentAccountId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted . ' notifications deleted'
        ]);
    }
}

