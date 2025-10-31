<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth');
        $this->notificationService = $notificationService;
    }

    /**
     * Mark a specific notification as read and redirect
     */
    public function markAsRead(Request $request, $notificationId)
    {
        $this->notificationService->markAsRead($notificationId);
        
        // Get the notification details to determine redirect
        $user = auth()->user();
        $notifications = $this->notificationService->getNotifications($user);
        
        $notification = collect($notifications)->firstWhere('id', $notificationId);
        
        // Return JSON response for AJAX request
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $notification['route'] ?? route('dashboard-index')
            ]);
        }
        
        // Regular redirect for non-AJAX
        if ($notification && isset($notification['route'])) {
            return redirect($notification['route']);
        }
        
        return redirect()->back();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $this->notificationService->markAllAsRead(auth()->user());
        
        return response()->json(['success' => true]);
    }

    /**
     * Get notifications (for AJAX requests)
     */
    public function getNotifications(Request $request)
    {
        $user = auth()->user();
        $notifications = $this->notificationService->getNotifications($user);
        $unreadCount = $this->notificationService->getUnreadCount($user);
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }
}