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
                    $this->getRescheduledAppointments($user),
                    $this->getVisitCreatedAlerts($user),
                    $this->getReferralAlerts($user),
                    $this->getBillingPaidAlerts($user),
                    $this->getBoardingCheckoutAlerts($user),
                    $this->getFollowUpAppointmentAlerts($user)
                );
                break;

            case 'veterinarian':
                $notifications = array_merge(
                    $this->getRescheduledAppointments($user),
                    $this->getArrivedAppointments($user),
                    $this->getVisitCreatedAlerts($user),
                    $this->getReferralAlerts($user),
                    $this->getFollowUpAppointmentAlerts($user)
                );
                break;

            case 'receptionist':
                $notifications = array_merge(
                    $this->getStockLevelAlerts($user),
                    $this->getProductExpirationAlerts($user),
                    $this->getArrivedAppointments($user),
                    $this->getBillingPaidAlerts($user),
                    $this->getBoardingCheckoutAlerts($user),
                    $this->getFollowUpAppointmentAlerts($user)
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
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
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
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            // Low stock products (using prod_reorderlevel)
            $lowStockProducts = Product::when($activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
                ->where('prod_category', '!=', 'Service')
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

            // Out of stock products (only from tbl_prod table, not services)
            $outOfStockProducts = Product::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
                ->where('prod_category', '!=', 'Service')
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
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $thirtyDaysFromNow = Carbon::now()->addDays(30);
            
            // --- FIXED QUERY FOR EXPIRING PRODUCTS ---
            $expiringProducts = Product::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
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
            $expiredProducts = Product::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
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
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            // Equipment with low quantity (less than 5)
            $lowEquipment = Equipment::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
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
            $outOfStockEquipment = Equipment::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
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

            // Equipment under maintenance
            $maintenanceEquipment = Equipment::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
                ->where('equipment_maintenance', 1)
                ->get();

            foreach ($maintenanceEquipment as $equipment) {
                $alerts[] = [
                    'id' => 'equipment_maintenance_' . $equipment->equipment_id,
                    'type' => 'equipment_alert',
                    'icon' => 'fa-wrench',
                    'color' => 'yellow',
                    'title' => 'Equipment Under Maintenance',
                    'message' => $equipment->equipment_name . ' is currently under maintenance',
                    'timestamp' => $equipment->updated_at ?? Carbon::now(),
                    'route' => route('prodservequip.index') . '?tab=equipment',
                    'is_read' => $this->isNotificationRead('equipment_maintenance_' . $equipment->equipment_id)
                ];
            }

            // Equipment out of service
            $outOfServiceEquipment = Equipment::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
                ->where('equipment_out_of_service', 1)
                ->get();

            foreach ($outOfServiceEquipment as $equipment) {
                $alerts[] = [
                    'id' => 'equipment_outofservice_' . $equipment->equipment_id,
                    'type' => 'equipment_alert',
                    'icon' => 'fa-times-circle',
                    'color' => 'red',
                    'title' => 'Equipment Out of Service',
                    'message' => $equipment->equipment_name . ' is out of service',
                    'timestamp' => $equipment->updated_at ?? Carbon::now(),
                    'route' => route('prodservequip.index') . '?tab=equipment',
                    'is_read' => $this->isNotificationRead('equipment_outofservice_' . $equipment->equipment_id)
                ];
            }

            // Equipment available for use
            $availableEquipment = Equipment::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('branch_id', $activeBranchId);
                })
                ->where('equipment_available', 1)
                ->where('equipment_maintenance', 0)
                ->where('equipment_out_of_service', 0)
                ->where('equipment_quantity', '>', 0)
                ->get();

            foreach ($availableEquipment as $equipment) {
                $alerts[] = [
                    'id' => 'equipment_available_' . $equipment->equipment_id,
                    'type' => 'equipment_alert',
                    'icon' => 'fa-check-circle',
                    'color' => 'green',
                    'title' => 'Equipment Available',
                    'message' => $equipment->equipment_name . ' is available for use (' . $equipment->equipment_quantity . ' units)',
                    'timestamp' => $equipment->updated_at ?? Carbon::now(),
                    'route' => route('prodservequip.index') . '?tab=equipment',
                    'is_read' => $this->isNotificationRead('equipment_available_' . $equipment->equipment_id)
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
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $rescheduledAppointments = Appointment::when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->whereHas('user', function($q) use ($activeBranchId) {
                        $q->where('branch_id', $activeBranchId);
                    });
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
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $arrivedAppointments = Appointment::with(['pet.owner'])
                ->when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->whereHas('user', function($q) use ($activeBranchId) {
                        $q->where('branch_id', $activeBranchId);
                    });
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

    /**
     * Get visit created alerts (last 24 hours)
     */
    private function getVisitCreatedAlerts($user)
    {
        $alerts = [];
        
        try {
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $recentVisits = DB::table('tbl_visit_record')
                ->join('tbl_pet', 'tbl_visit_record.pet_id', '=', 'tbl_pet.pet_id')
                ->leftJoin('tbl_user', 'tbl_user.user_id', '=', 'tbl_visit_record.user_id')
                ->leftJoin('tbl_own', 'tbl_pet.own_id', '=', 'tbl_own.own_id')
                ->where('tbl_visit_record.created_at', '>=', Carbon::now()->subDay())
                ->when($activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('tbl_user.branch_id', $activeBranchId);
                })
                ->orderBy('tbl_visit_record.created_at', 'desc')
                ->limit(10)
                ->select('tbl_visit_record.*', 'tbl_pet.pet_name', 'tbl_own.own_name')
                ->get();

            foreach ($recentVisits as $visit) {
                $alerts[] = [
                    'id' => 'visit_' . $visit->visit_id,
                    'type' => 'visit_alert',
                    'icon' => 'fa-notes-medical',
                    'color' => 'blue',
                    'title' => 'New Visit Created',
                    'message' => 'Visit for ' . ($visit->pet_name ?? 'Pet') . ' (Owner: ' . ($visit->own_name ?? 'Unknown') . ')',
                    'timestamp' => $visit->created_at,
                    'route' => route('medical.index'),
                    'is_read' => $this->isNotificationRead('visit_' . $visit->visit_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Visit created alerts error: ' . $e->getMessage());
        }

        return $alerts;
    }

    /**
     * Get referral alerts (last 7 days)
     */
    private function getReferralAlerts($user)
    {
        $alerts = [];
        
        try {
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $recentReferrals = DB::table('tbl_ref')
                ->join('tbl_pet', 'tbl_ref.pet_id', '=', 'tbl_pet.pet_id')
                ->where('tbl_ref.ref_date', '>=', Carbon::now()->subDays(7))
                ->when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where(function($q) use ($activeBranchId) {
                        $q->where('tbl_ref.ref_from', $activeBranchId)
                          ->orWhere('tbl_ref.ref_to', $activeBranchId);
                    });
                })
                ->orderBy('tbl_ref.ref_date', 'desc')
                ->select('tbl_ref.*', 'tbl_pet.pet_name')
                ->get();

            foreach ($recentReferrals as $referral) {
                $alerts[] = [
                    'id' => 'referral_' . $referral->ref_id,
                    'type' => 'referral_alert',
                    'icon' => 'fa-share-square',
                    'color' => 'purple',
                    'title' => 'New Referral',
                    'message' => ($referral->pet_name ?? 'Pet') . ' referred to ' . ($referral->company_name ?? 'External Clinic'),
                    'timestamp' => $referral->ref_date,
                    'route' => route('care-continuity.index') . '?active_tab=referrals',
                    'is_read' => $this->isNotificationRead('referral_' . $referral->ref_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Referral alerts error: ' . $e->getMessage());
        }

        return $alerts;
    }

    /**
     * Get billing paid alerts (last 7 days)
     */
    private function getBillingPaidAlerts($user)
    {
        $alerts = [];
        
        try {
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $recentPayments = DB::table('tbl_pay')
                ->join('tbl_bill', 'tbl_pay.bill_id', '=', 'tbl_bill.bill_id')
                ->join('tbl_visit_record', 'tbl_bill.visit_id', '=', 'tbl_visit_record.visit_id')
                ->join('tbl_pet', 'tbl_visit_record.pet_id', '=', 'tbl_pet.pet_id')
                ->where('tbl_pay.created_at', '>=', Carbon::now()->subDays(7))
                /*->when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('tbl_visit_record.branch_id', $activeBranchId);
                })*/
                ->orderBy('tbl_pay.created_at', 'desc')
                ->select('tbl_pay.*', 'tbl_pet.pet_name', 'tbl_bill.total_amount')
                ->get();

            foreach ($recentPayments as $payment) {
                $alerts[] = [
                    'id' => 'payment_' . $payment->pay_id,
                    'type' => 'payment_alert',
                    'icon' => 'fa-money-bill-wave',
                    'color' => 'green',
                    'title' => 'Payment Received',
                    'message' => 'Payment of ₱' . number_format($payment->pay_total, 2) . ' received for ' . ($payment->pet_name ?? 'Pet'),
                    'timestamp' => $payment->created_at,
                    'route' => route('sales.index'),
                    'is_read' => $this->isNotificationRead('payment_' . $payment->pay_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Payment alerts error: ' . $e->getMessage());
        }

        return $alerts;
    }

    /**
     * Get boarding checkout alerts (pets checking out today or overdue)
     */
    private function getBoardingCheckoutAlerts($user)
    {
        $alerts = [];
        
        try {
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $checkouts = DB::table('tbl_boarding_record')
                ->join('tbl_visit_record', 'tbl_boarding_record.visit_id', '=', 'tbl_visit_record.visit_id')
                ->join('tbl_pet', 'tbl_boarding_record.pet_id', '=', 'tbl_pet.pet_id')
                ->where(function($query) {
                    $query->whereDate('tbl_boarding_record.check_out_date', '<=', Carbon::today())
                          ->orWhereNull('tbl_boarding_record.check_out_date');
                })
                ->where('tbl_boarding_record.status', '!=', 'completed')
               /* ->when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->where('tbl_visit_record.branch_id', $activeBranchId);
                })*/
                ->orderBy('tbl_boarding_record.check_out_date', 'asc')
                ->select('tbl_boarding_record.*', 'tbl_pet.pet_name')
                ->get();

            foreach ($checkouts as $boarding) {
                $isOverdue = $boarding->check_out_date && Carbon::parse($boarding->check_out_date)->isPast();
                
                $alerts[] = [
                    'id' => 'boarding_' . $boarding->board_id,
                    'type' => 'boarding_alert',
                    'icon' => 'fa-door-open',
                    'color' => $isOverdue ? 'red' : 'orange',
                    'title' => $isOverdue ? 'Boarding Overdue' : 'Boarding Checkout Today',
                    'message' => ($boarding->pet_name ?? 'Pet') . ' ' . ($isOverdue ? 'overdue for checkout' : 'checking out today'),
                    'timestamp' => $boarding->check_out_date ?? $boarding->updated_at,
                    'route' => route('medical.index') . '?tab=boarding',
                    'is_read' => $this->isNotificationRead('boarding_' . $boarding->board_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Boarding checkout alerts error: ' . $e->getMessage());
        }

        return $alerts;
    }

    /**
     * Get follow-up appointment alerts (next 7 days)
     */
    private function getFollowUpAppointmentAlerts($user)
    {
        $alerts = [];
        
        try {
            $activeBranchId = session('active_branch_id') ?? $user->branch_id;
            
            $isSuperAdmin = strtolower(trim($user->user_role)) === 'superadmin';
            
            $followUps = Appointment::with(['pet.owner'])
                ->when( $activeBranchId, function($query) use ($activeBranchId) {
                    return $query->whereHas('user', function($q) use ($activeBranchId) {
                        $q->where('branch_id', $activeBranchId);
                    });
                })
                ->where(function($query) {
                    $query->where('appoint_type', 'LIKE', '%follow%')
                          ->orWhere('appoint_type', 'LIKE', '%recheck%');
                })
                ->whereIn('appoint_status', ['scheduled', 'confirmed'])
                ->whereBetween('appoint_date', [Carbon::today(), Carbon::today()->addDays(7)])
                ->orderBy('appoint_date', 'asc')
                ->get();

            foreach ($followUps as $appointment) {
                $daysUntil = Carbon::today()->diffInDays(Carbon::parse($appointment->appoint_date));
                $isToday = $daysUntil == 0;
                
                $alerts[] = [
                    'id' => 'followup_' . $appointment->appoint_id,
                    'type' => 'followup_alert',
                    'icon' => 'fa-calendar-check',
                    'color' => $isToday ? 'red' : 'blue',
                    'title' => $isToday ? 'Follow-up Today' : 'Upcoming Follow-up',
                    'message' => ($appointment->pet->pet_name ?? 'Pet') . ' - ' . $appointment->appoint_type . ' in ' . $daysUntil . ' day' . ($daysUntil != 1 ? 's' : ''),
                    'timestamp' => $appointment->created_at,
                    'route' => route('care-continuity.index') . '?active_tab=appointments',
                    'is_read' => $this->isNotificationRead('followup_' . $appointment->appoint_id)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Follow-up appointment alerts error: ' . $e->getMessage());
        }

        return $alerts;
    }
}