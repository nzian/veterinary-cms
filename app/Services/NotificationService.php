<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Equipment;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Get all notifications for the current user
     */
    public function getNotifications($user)
    {
        $notifications = [];
        $role = strtolower(trim($user->user_role));

        switch ($role) {
            case 'superadmin':
                $notifications = array_merge(
                    $this->getLoginAlerts($user),
                    $this->getStockLevelAlerts($user),
                    $this->getProductExpirationAlerts($user),
                    $this->getEquipmentStatusAlerts($user)
                );
                break;

            case 'veterinarian':
                $notifications = array_merge(
                    $this->getRescheduledAppointments($user),
                    $this->getArrivedAppointments($user)
                );
                break;

            case 'receptionist':
                $notifications = array_merge(
                    $this->getStockLevelAlerts($user),
                    $this->getProductExpirationAlerts($user)
                );
                break;
        }

        // Sort by timestamp (newest first)
        usort($notifications, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return $notifications;
    }

    /**
     * Get login alerts (last 24 hours)
     */
    private function getLoginAlerts($user)
    {
        $alerts = [];
        
        try {
            // Get users who logged in within the last 24 hours
            $recentLogins = User::where('last_login_at', '>=', Carbon::now()->subDay())
                ->where('user_id', '!=', $user->user_id)
                ->where('branch_id', $user->branch_id)
                ->orderBy('last_login_at', 'desc')
                ->get();

            foreach ($recentLogins as $login) {
                $alerts[] = [
                    'id' => 'login_' . $login->user_id . '_' . strtotime($login->last_login_at),
                    'type' => 'login_alert',
                    'icon' => 'fa-user-circle',
                    'color' => 'blue',
                    'title' => 'User Login',
                    'message' => ucfirst($login->user_role) . ' "' . ($login->name ?? 'User') . '" logged in',
                    'timestamp' => $login->last_login_at,
                    'route' => route('dashboard-index'), // Your actual route
                    'is_read' => $this->isNotificationRead('login_' . $login->user_id . '_' . strtotime($login->last_login_at))
                ];
            }
        } catch (\Exception $e) {
            // Handle error silently
        }

        return $alerts;
    }

    /**
     * Get stock level alerts (products below minimum stock)
     */
    private function getStockLevelAlerts($user)
    {
        $alerts = [];
        
        try {
            $lowStockProducts = Product::where('branch_id', $user->branch_id)
                ->whereRaw('prod_stocks <= prod_min_stock')
                ->where('prod_stocks', '>', 0)
                ->get();

            foreach ($lowStockProducts as $product) {
                $alerts[] = [
                    'id' => 'stock_' . $product->prod_id,
                    'type' => 'stock_alert',
                    'icon' => 'fa-box',
                    'color' => 'orange',
                    'title' => 'Low Stock Alert',
                    'message' => $product->prod_name . ' has only ' . $product->prod_stocks . ' items left (Min: ' . $product->prod_min_stock . ')',
                    'timestamp' => $product->updated_at,
                    'route' => route('prodservequip.index') . '?tab=products', // Your actual route
                    'is_read' => $this->isNotificationRead('stock_' . $product->prod_id)
                ];
            }

            // Out of stock products
            $outOfStockProducts = Product::where('branch_id', $user->branch_id)
                ->where('prod_stocks', 0)
                ->get();

            foreach ($outOfStockProducts as $product) {
                $alerts[] = [
                    'id' => 'outofstock_' . $product->prod_id,
                    'type' => 'stock_alert',
                    'icon' => 'fa-exclamation-triangle',
                    'color' => 'red',
                    'title' => 'Out of Stock',
                    'message' => $product->prod_name . ' is out of stock!',
                    'timestamp' => $product->updated_at,
                    'route' => route('prodservequip.index') . '?tab=products', // Your actual route
                    'is_read' => $this->isNotificationRead('outofstock_' . $product->prod_id)
                ];
            }
        } catch (\Exception $e) {
            // Handle error silently
        }

        return $alerts;
    }

    /**
     * Get product expiration alerts (expiring within 30 days)
     */
    private function getProductExpirationAlerts($user)
    {
        $alerts = [];
        
        try {
            $thirtyDaysFromNow = Carbon::now()->addDays(30);
            
            $expiringProducts = Product::where('branch_id', $user->branch_id)
                ->whereNotNull('prod_expiration')
                ->where('prod_expiration', '<=', $thirtyDaysFromNow)
                ->where('prod_expiration', '>=', Carbon::now())
                ->orderBy('prod_expiration', 'asc')
                ->get();

            foreach ($expiringProducts as $product) {
                $daysUntilExpiry = Carbon::now()->diffInDays($product->prod_expiration);
                $isUrgent = $daysUntilExpiry <= 7;

                $alerts[] = [
                    'id' => 'expiry_' . $product->prod_id,
                    'type' => 'expiration_alert',
                    'icon' => 'fa-calendar-times',
                    'color' => $isUrgent ? 'red' : 'yellow',
                    'title' => $isUrgent ? 'Urgent: Product Expiring Soon' : 'Product Expiration Warning',
                    'message' => $product->prod_name . ' expires in ' . $daysUntilExpiry . ' days (' . Carbon::parse($product->prod_expiration)->format('M d, Y') . ')',
                    'timestamp' => $product->updated_at,
                    'route' => route('prodservequip.index') . '?tab=products', // Your actual route
                    'is_read' => $this->isNotificationRead('expiry_' . $product->prod_id)
                ];
            }

            // Expired products
            $expiredProducts = Product::where('branch_id', $user->branch_id)
                ->whereNotNull('prod_expiration')
                ->where('prod_expiration', '<', Carbon::now())
                ->get();

            foreach ($expiredProducts as $product) {
                $alerts[] = [
                    'id' => 'expired_' . $product->prod_id,
                    'type' => 'expiration_alert',
                    'icon' => 'fa-ban',
                    'color' => 'red',
                    'title' => 'Product Expired',
                    'message' => $product->prod_name . ' has expired! Remove from inventory immediately.',
                    'timestamp' => $product->prod_expiration,
                    'route' => route('prodservequip.index') . '?tab=products', // Your actual route
                    'is_read' => $this->isNotificationRead('expired_' . $product->prod_id)
                ];
            }
        } catch (\Exception $e) {
            // Handle error silently
        }

        return $alerts;
    }

    /**
     * Get equipment status alerts
     */
    private function getEquipmentStatusAlerts($user)
    {
        $alerts = [];
        
        try {
            // Equipment needing attention (not operational)
            $equipmentNeedingAttention = Equipment::where('branch_id', $user->branch_id)
                ->where('equip_status', '!=', 'Operational')
                ->get();

            foreach ($equipmentNeedingAttention as $equipment) {
                $alerts[] = [
                    'id' => 'equipment_' . $equipment->equip_id,
                    'type' => 'equipment_alert',
                    'icon' => 'fa-tools',
                    'color' => 'purple',
                    'title' => 'Equipment Status Alert',
                    'message' => $equipment->equip_name . ' status: ' . $equipment->equip_status,
                    'timestamp' => $equipment->updated_at,
                    'route' => route('prodservequip.index') . '?tab=equipment', // Your actual route
                    'is_read' => $this->isNotificationRead('equipment_' . $equipment->equip_id)
                ];
            }
        } catch (\Exception $e) {
            // Handle error silently
        }

        return $alerts;
    }

    /**
     * Get rescheduled appointments
     */
    private function getRescheduledAppointments($user)
    {
        $alerts = [];
        
        try {
            $rescheduledAppointments = Appointment::where('branch_id', $user->branch_id)
                ->where('appt_status', 'Rescheduled')
                ->where('updated_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('appt_date', 'asc')
                ->get();

            foreach ($rescheduledAppointments as $appointment) {
                $alerts[] = [
                    'id' => 'reschedule_' . $appointment->appt_id,
                    'type' => 'reschedule_alert',
                    'icon' => 'fa-calendar-alt',
                    'color' => 'blue',
                    'title' => 'Appointment Rescheduled',
                    'message' => 'Appointment #' . $appointment->appt_id . ' rescheduled to ' . Carbon::parse($appointment->appt_date)->format('M d, Y g:i A'),
                    'timestamp' => $appointment->updated_at,
                    'route' => route('medical.index') . '?active_tab=appointments', // Your actual route
                    'is_read' => $this->isNotificationRead('reschedule_' . $appointment->appt_id)
                ];
            }
        } catch (\Exception $e) {
            // Handle error silently
        }

        return $alerts;
    }

    /**
     * Get arrived appointments (today)
     */
    private function getArrivedAppointments($user)
    {
        $alerts = [];
        
        try {
            $arrivedAppointments = Appointment::where('branch_id', $user->branch_id)
                ->where('appt_status', 'Arrived')
                ->whereDate('appt_date', Carbon::today())
                ->orderBy('updated_at', 'desc')
                ->get();

            foreach ($arrivedAppointments as $appointment) {
                $alerts[] = [
                    'id' => 'arrived_' . $appointment->appt_id,
                    'type' => 'arrived_alert',
                    'icon' => 'fa-check-circle',
                    'color' => 'green',
                    'title' => 'Patient Arrived',
                    'message' => 'Appointment #' . $appointment->appt_id . ' - Patient has arrived',
                    'timestamp' => $appointment->updated_at,
                    'route' => route('medical.index') . '?active_tab=appointments', // Your actual route
                    'is_read' => $this->isNotificationRead('arrived_' . $appointment->appt_id)
                ];
            }
        } catch (\Exception $e) {
            // Handle error silently
        }

        return $alerts;
    }

    /**
     * Check if notification is read (stored in session)
     */
    private function isNotificationRead($notificationId)
    {
        $readNotifications = session('read_notifications', []);
        return in_array($notificationId, $readNotifications);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId)
    {
        $readNotifications = session('read_notifications', []);
        if (!in_array($notificationId, $readNotifications)) {
            $readNotifications[] = $notificationId;
            session(['read_notifications' => $readNotifications]);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($user)
    {
        $allNotifications = $this->getNotifications($user);
        $notificationIds = array_column($allNotifications, 'id');
        session(['read_notifications' => $notificationIds]);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount($user)
    {
        $allNotifications = $this->getNotifications($user);
        $unreadNotifications = array_filter($allNotifications, function ($notification) {
            return !$notification['is_read'];
        });
        return count($unreadNotifications);
    }

    public function notifyAppointmentArrived(Appointment $appointment)
    {
        $alerts = [];

        try {
            // Notify all vets and receptionists in the branch
            $usersToNotify = User::where('branch_id', $appointment->branch_id)
                ->whereIn('user_role', ['Veterinarian', 'Receptionist'])
                ->get();

            foreach ($usersToNotify as $user) {
                $alerts[] = [
                    'id' => 'arrived_' . $appointment->appt_id,
                    'type' => 'arrived_alert',
                    'icon' => 'fa-check-circle',
                    'color' => 'green',
                    'title' => 'Patient Arrived',
                    'message' => 'Appointment #' . $appointment->appt_id . ' - Patient has arrived',
                    'timestamp' => now(),
                    'route' => route('medical.index') . '?active_tab=appointments',
                    'is_read' => false
                ];
            }

            // Optionally: store these in session or database for later retrieval
            // Here we append to session-based notifications
            $readNotifications = session('read_notifications', []);
            foreach ($alerts as $alert) {
                if (!in_array($alert['id'], $readNotifications)) {
                    $notifications = session('notifications', []);
                    $notifications[] = $alert;
                    session(['notifications' => $notifications]);
                }
            }

        } catch (\Exception $e) {
            \Log::error('Error notifying appointment arrival: ' . $e->getMessage());
        }

        return $alerts;
    }

    
}