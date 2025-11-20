<?php

namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Equipment;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                    $this->getEquipmentStatusAlerts($user),
                    $this->getArrivedAppointments($user),
                    $this->getRescheduledAppointments($user)
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
                    $this->getProductExpirationAlerts($user),
                    $this->getArrivedAppointments($user)
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
            // Get the active branch ID (for super admin in branch mode)
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            // Get users who logged in within the last 24 hours
            $recentLogins = User::where('updated_at', '>=', Carbon::now()->subDay())
                ->where('user_id', '!=', $user->user_id)
                ->when($activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();

            foreach ($recentLogins as $login) {
                $alerts[] = [
                    'id' => 'login_' . $login->user_id . '_' . strtotime($login->updated_at),
                    'type' => 'login_alert',
                    'icon' => 'fa-user-circle',
                    'color' => 'blue',
                    'title' => 'User Login',
                    'message' => ucfirst($login->user_role) . ' "' . ($login->user_name ?? 'User') . '" logged in',
                    'timestamp' => $login->updated_at,
                    'route' => route('dashboard-index'),
                    'is_read' => $this->isNotificationRead('login_' . $login->user_id . '_' . strtotime($login->updated_at))
                ];
            }
        } catch (\Exception $e) {
            Log::error('Login alerts error: ' . $e->getMessage());
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
            // Get the active branch ID
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            // Low stock products (using prod_reorderlevel)
            $lowStockProducts = Product::where('branch_id', $activeBranchId)
                ->whereColumn('prod_stocks', '<=', 'prod_reorderlevel')
                ->where('prod_stocks', '>', 0)
                ->get();

            foreach ($lowStockProducts as $product) {
                $alerts[] = [
                    'id' => 'stock_' . $product->prod_id,
                    'type' => 'stock_alert',
                    'icon' => 'fa-box',
                    'color' => 'orange',
                    'title' => 'Low Stock Alert',
                    'message' => $product->prod_name . ' has only ' . $product->prod_stocks . ' items left (Reorder level: ' . $product->prod_reorderlevel . ')',
                    'timestamp' => $product->updated_at,
                    'route' => route('prodservequip.index') . '?tab=products',
                    'is_read' => $this->isNotificationRead('stock_' . $product->prod_id)
                ];
            }

            // Out of stock products
            $outOfStockProducts = Product::where('branch_id', $activeBranchId)
                ->where('prod_stocks', '=', 0)
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
                    'route' => route('prodservequip.index') . '?tab=products',
                    'is_read' => $this->isNotificationRead('outofstock_' . $product->prod_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Stock alerts error: ' . $e->getMessage());
        }

        return $alerts;
    }

    /**
     * Get product expiration alerts (expiring within 30 days)
     */
   
    /**
     * Get product expiration alerts (expiring within 30 days)
     */
    private function getProductExpirationAlerts($user)
    {
        $alerts = [];
        
        try {
            // Get the active branch ID
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $thirtyDaysFromNow = Carbon::now()->addDays(30);
            
            // --- FIXED QUERY FOR EXPIRING PRODUCTS ---
            $expiringProducts = Product::where('branch_id', $activeBranchId)
                ->where(function($query) use ($thirtyDaysFromNow) {
                    // Check for products with an expiration date that falls in the next 30 days
                    $query->whereNotNull('prod_expiry')
                          ->where('prod_expiry', '<=', $thirtyDaysFromNow)
                          ->where('prod_expiry', '>=', Carbon::now());
                })
                // Sort only by the existing column
                ->orderBy('prod_expiry', 'ASC')
                ->get();

            foreach ($expiringProducts as $product) {
                // We know $product->prod_expiry is not null here due to the query filter
                $expiryDate = $product->prod_expiry;
                if ($expiryDate) {
                    $daysUntilExpiry = Carbon::now()->diffInDays($expiryDate);
                    $isUrgent = $daysUntilExpiry <= 7;

                    $alerts[] = [
                        'id' => 'expiry_' . $product->prod_id,
                        'type' => 'expiration_alert',
                        'icon' => 'fa-calendar-times',
                        'color' => $isUrgent ? 'red' : 'yellow',
                        'title' => $isUrgent ? 'Urgent: Product Expiring Soon' : 'Product Expiration Warning',
                        'message' => $product->prod_name . ' expires in ' . $daysUntilExpiry . ' days (' . Carbon::parse($expiryDate)->format('M d, Y') . ')',
                        'timestamp' => $product->updated_at,
                        'route' => route('prodservequip.index') . '?tab=products',
                        'is_read' => $this->isNotificationRead('expiry_' . $product->prod_id)
                    ];
                }
            }

            // --- FIXED QUERY FOR EXPIRED PRODUCTS ---
            $expiredProducts = Product::where('branch_id', $activeBranchId)
                ->whereNotNull('prod_expiry') // Only check products that *can* expire
                ->where('prod_expiry', '<', Carbon::now())
                ->get();

            foreach ($expiredProducts as $product) {
                $expiryDate = $product->prod_expiry;
                $alerts[] = [
                    'id' => 'expired_' . $product->prod_id,
                    'type' => 'expiration_alert',
                    'icon' => 'fa-ban',
                    'color' => 'red',
                    'title' => 'Product Expired',
                    'message' => $product->prod_name . ' has expired! Update inventory immediately.',
                    'timestamp' => $expiryDate,
                    'route' => route('prodservequip.index') . '?tab=products',
                    'is_read' => $this->isNotificationRead('expired_' . $product->prod_id)
                ];
            }
        } catch (\Exception $e) {
            // Log the fixed query error message
            Log::error('Expiration alerts error: ' . $e->getMessage()); 
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
            // Get the active branch ID
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            // Equipment with low quantity (less than 5)
            $lowEquipment = Equipment::where('branch_id', $activeBranchId)
                ->where('equipment_quantity', '<', 5)
                ->where('equipment_quantity', '>', 0)
                ->get();

            foreach ($lowEquipment as $equipment) {
                $alerts[] = [
                    'id' => 'equipment_low_' . $equipment->equipment_id,
                    'type' => 'equipment_alert',
                    'icon' => 'fa-tools',
                    'color' => 'orange',
                    'title' => 'Equipment Low Quantity',
                    'message' => $equipment->equipment_name . ' has only ' . $equipment->equipment_quantity . ' units left',
                    'timestamp' => $equipment->updated_at,
                    'route' => route('prodservequip.index') . '?tab=equipment',
                    'is_read' => $this->isNotificationRead('equipment_low_' . $equipment->equipment_id)
                ];
            }

            // Out of stock equipment
            $outOfStockEquipment = Equipment::where('branch_id', $activeBranchId)
                ->where('equipment_quantity', '=', 0)
                ->get();

            foreach ($outOfStockEquipment as $equipment) {
                $alerts[] = [
                    'id' => 'equipment_out_' . $equipment->equipment_id,
                    'type' => 'equipment_alert',
                    'icon' => 'fa-exclamation-triangle',
                    'color' => 'red',
                    'title' => 'Equipment Out of Stock',
                    'message' => $equipment->equipment_name . ' is out of stock!',
                    'timestamp' => $equipment->updated_at,
                    'route' => route('prodservequip.index') . '?tab=equipment',
                    'is_read' => $this->isNotificationRead('equipment_out_' . $equipment->equipment_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Equipment alerts error: ' . $e->getMessage());
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
            // Get the active branch ID
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $rescheduledAppointments = Appointment::whereHas('user', function($query) use ($activeBranchId) {
                    $query->where('branch_id', $activeBranchId);
                })
                ->where('appoint_status', 'rescheduled')
                ->where('updated_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('appoint_date', 'asc')
                ->get();

            foreach ($rescheduledAppointments as $appointment) {
                $petName = optional($appointment->pet)->pet_name ?? 'Pet';

                $alerts[] = [
                    'id' => 'reschedule_' . $appointment->appoint_id,
                    'type' => 'reschedule_alert',
                    'icon' => 'fa-calendar-alt',
                    'color' => 'blue',
                    'title' => 'Appointment Rescheduled',
                    'message' => 'Appointment for ' . $petName . ' rescheduled to ' . Carbon::parse($appointment->appoint_date)->format('M d, Y') . ' at ' . $appointment->appoint_time,
                    'timestamp' => $appointment->updated_at,
                    'route' => route('care-continuity.index') . '?active_tab=appointments',
                    'is_read' => $this->isNotificationRead('reschedule_' . $appointment->appoint_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Rescheduled appointments error: ' . $e->getMessage());
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
            // Get the active branch ID
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $arrivedAppointments = Appointment::with(['pet.owner'])
                ->whereHas('user', function($query) use ($activeBranchId) {
                    $query->where('branch_id', $activeBranchId);
                })
                ->where('appoint_status', 'arrived')
                ->whereDate('appoint_date', Carbon::today())
                ->orderBy('updated_at', 'desc')
                ->get();

            foreach ($arrivedAppointments as $appointment) {
                $pet = optional($appointment->pet);
                $petName = $pet->pet_name ?? 'Unknown Pet';
                $ownerName = optional($pet->owner)->own_name ?? 'Unknown Owner';

                $alerts[] = [
                    'id' => 'arrived_' . $appointment->appoint_id,
                    'type' => 'arrived_alert',
                    'icon' => 'fa-check-circle',
                    'color' => 'green',
                    'title' => 'Patient Arrived',
                    'message' => $petName . ' (Owner: ' . $ownerName . ') has arrived for their appointment',
                    'timestamp' => $appointment->updated_at,
                    'route' => route('care-continuity.index') . '?active_tab=appointments',
                    'is_read' => $this->isNotificationRead('arrived_' . $appointment->appoint_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Arrived appointments error: ' . $e->getMessage());
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

    /**
     * Notify when appointment status changes to arrived
     */
    public function notifyAppointmentArrived(Appointment $appointment)
    {
        // This method is called when appointment status changes to 'arrived'
        // The notifications will automatically appear for vets and receptionists
        // when they refresh or check their notifications
        
        Log::info('Appointment arrived notification triggered for appointment: ' . $appointment->appoint_id);
    }
}