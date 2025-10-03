<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class NotificationService
{
    /**
     * Add a notification to session
     */
    private function addNotification($userId, $notification)
    {
        $key = "notifications_user_{$userId}";
        $notifications = Session::get($key, []);
        
        $notification['id'] = uniqid();
        $notification['created_at'] = now()->toISOString();
        $notification['is_read'] = false;
        
        array_unshift($notifications, $notification);
        
        // Keep only last 50 notifications
        $notifications = array_slice($notifications, 0, 50);
        
        Session::put($key, $notifications);
    }

    /**
     * Notify veterinarians when appointment status changes to 'arrived'
     */
    public function notifyAppointmentArrived($appointment)
    {
        try {
            $veterinarians = \App\Models\User::where('branch_id', auth()->user()->branch_id ?? $appointment->user->branch_id)
                ->where('user_role', 'Veterinarian')
                ->where('user_id', '!=', auth()->id())
                ->get();

            $petName = $appointment->pet->pet_name ?? 'Unknown Pet';
            $ownerName = $appointment->pet->owner->own_name ?? 'Unknown Owner';
            $appointmentTime = \Carbon\Carbon::parse($appointment->appoint_time)->format('g:i A');

            foreach ($veterinarians as $vet) {
                $this->addNotification($vet->user_id, [
                    'type' => 'appointment_arrived',
                    'title' => 'Patient Arrived',
                    'message' => "{$petName} (Owner: {$ownerName}) has arrived for their {$appointmentTime} appointment.",
                    'data' => [
                        'appointment_id' => $appointment->appoint_id,
                        'pet_name' => $petName,
                        'owner_name' => $ownerName,
                        'appointment_time' => $appointmentTime,
                        'icon' => 'fa-user-check'
                    ]
                ]);
            }

            Log::info("Arrival notification sent for appointment {$appointment->appoint_id}");
        } catch (\Exception $e) {
            Log::error("Failed to send arrival notification: " . $e->getMessage());
        }
    }

    /**
     * Notify superadmin when a user logs in
     */
    public function notifyUserLogin($user)
    {
        try {
            $superAdmins = \App\Models\User::where('user_role', 'SuperAdmin')->get();
            $branchName = $user->branch->branch_name ?? 'Unknown Branch';

            foreach ($superAdmins as $admin) {
                $this->addNotification($admin->user_id, [
                    'type' => 'user_login',
                    'title' => 'User Login',
                    'message' => "{$user->user_name} ({$user->user_role}) logged in from {$branchName}.",
                    'data' => [
                        'user_name' => $user->user_name,
                        'user_role' => $user->user_role,
                        'branch_name' => $branchName,
                        'login_time' => now()->format('g:i A'),
                        'icon' => 'fa-sign-in-alt'
                    ]
                ]);
            }

            Log::info("Login notification sent for user {$user->user_id}");
        } catch (\Exception $e) {
            Log::error("Failed to send login notification: " . $e->getMessage());
        }
    }

    /**
     * Notify veterinarians in target branch when referral is received
     */
    public function notifyReferralReceived($referral)
    {
        try {
            $veterinarians = \App\Models\User::where('branch_id', $referral->ref_to)
                ->where('user_role', 'Veterinarian')
                ->get();

            $appointment = $referral->appointment;
            $petName = $appointment->pet->pet_name ?? 'Unknown Pet';
            $ownerName = $appointment->pet->owner->own_name ?? 'Unknown Owner';
            $fromBranch = $referral->refByBranch->branch_name ?? 'Unknown Branch';
            $toBranch = $referral->refToBranch->branch_name ?? 'Your Branch';

            foreach ($veterinarians as $vet) {
                $this->addNotification($vet->user_id, [
                    'type' => 'referral_received',
                    'title' => 'New Referral Received',
                    'message' => "{$petName} (Owner: {$ownerName}) has been referred from {$fromBranch} to {$toBranch}.",
                    'data' => [
                        'referral_id' => $referral->ref_id,
                        'appointment_id' => $referral->appoint_id,
                        'pet_name' => $petName,
                        'owner_name' => $ownerName,
                        'from_branch' => $fromBranch,
                        'reason' => $referral->ref_description,
                        'icon' => 'fa-exchange-alt'
                    ]
                ]);
            }

            Log::info("Referral notification sent for referral {$referral->ref_id}");
        } catch (\Exception $e) {
            Log::error("Failed to send referral notification: " . $e->getMessage());
        }
    }

    /**
     * Get all notifications for current user
     */
    public static function getUserNotifications($userId)
    {
        $key = "notifications_user_{$userId}";
        $notifications = Session::get($key, []);
        
        // Convert to collection-like array
        return collect($notifications)->map(function($notification) {
            $notification['created_at'] = \Carbon\Carbon::parse($notification['created_at']);
            return (object) $notification;
        });
    }

    /**
     * Get unread count
     */
    public static function getUnreadCount($userId)
    {
        $notifications = self::getUserNotifications($userId);
        return $notifications->where('is_read', false)->count();
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead($userId, $notificationId)
    {
        $key = "notifications_user_{$userId}";
        $notifications = Session::get($key, []);
        
        foreach ($notifications as &$notification) {
            if ($notification['id'] === $notificationId) {
                $notification['is_read'] = true;
                $notification['read_at'] = now()->toISOString();
                break;
            }
        }
        
        Session::put($key, $notifications);
    }

    /**
     * Mark all as read
     */
    public static function markAllAsRead($userId)
    {
        $key = "notifications_user_{$userId}";
        $notifications = Session::get($key, []);
        
        foreach ($notifications as &$notification) {
            $notification['is_read'] = true;
            $notification['read_at'] = now()->toISOString();
        }
        
        Session::put($key, $notifications);
    }
}