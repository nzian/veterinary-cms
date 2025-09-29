<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\PetManagementController;
use App\Http\Controllers\BranchManagementController;
use App\Http\Controllers\SalesManagementController;


// Register
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

// Login 
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Reset Pass
Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');

//Layout
Route::get('/admin', [AdminController::class, 'AdminBoard']);
Route::get('/dashboard', [dashboardController::class, 'index'])->name('dashboard-index');
//Route::get('/select-branch', [BranchController::class, 'select'])->name('select-branch');


// User Management Routes
Route::get('/user-management', [UserManagementController::class, 'index'])->name('userManagement.index');
//Route::post('/user-management/store', [UserManagementController::class, 'store'])->name('userManagement.store');
//Route::get('/user-management/{user}/edit', [UserManagementController::class, 'edit'])->name('userManagement.edit'); // for showing edit form
//Route::put('/user-management/{user}', [UserManagementController::class, 'update'])->name('userManagement.update'); // for updating user
//Route::delete('/user-management/{id}', [UserManagementController::class, 'destroy'])->name('userManagement.destroy');


// POS Routes
Route::get('/pos', [POSController::class, 'index'])->name('pos');
Route::post('/pos', [POSController::class, 'store'])->name('pos.store'); // Direct product sales
Route::post('/pos/pay-billing/{billingId}', [POSController::class, 'payBilling'])->name('pos.pay-billing'); // Pay existing bills
Route::get('/pos/billing-details/{billingId}', [POSController::class, 'getBillingDetails'])->name('pos.billing-details'); // Optional: Get bill details

//Orders routes
Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
Route::get('/api/orders/{id}', [OrderController::class, 'getOrderDetails']);
Route::get('/api/sales-summary', [OrderController::class, 'salesSummary'])->name('orders.sales-summary');
Route::get('/api/daily-sales', [OrderController::class, 'dailySales'])->name('orders.daily-sales');
Route::get('/api/top-products', [OrderController::class, 'topProducts'])->name('orders.top-products');  
    // Sales Analytics Routes
    Route::get('/sales/summary', [OrderController::class, 'salesSummary'])->name('sales.summary');
    Route::get('/sales/daily', [OrderController::class, 'dailySales'])->name('sales.daily');
    
//});

use App\Http\Controllers\ProdServEquipController;

// Main page showing all 4 tabs
Route::get('/prodservequip', [ProdServEquipController::class, 'index'])->name('prodservequip.index');
// Product routes
Route::get('/product', [ProductController::class, 'index'])->name('product-index');
Route::post('/products', [ProdServEquipController::class, 'storeProduct'])->name('products.store');
Route::put('/products/{id}', [ProdServEquipController::class, 'updateProduct'])->name('products.update');
Route::delete('/products/{id}', [ProdServEquipController::class, 'deleteProduct'])->name('products.destroy');
Route::get('/products/search', [ProductController::class, 'searchProducts'])->name('products.search');
// Service routes
Route::get('/services', [ServiceController::class, 'index'])->name('services-index');
Route::post('/services', [ProdServEquipController::class, 'storeService'])->name('services.store');
Route::put('/services/{id}', [ProdServEquipController::class, 'updateService'])->name('services.update');
Route::delete('/services/{id}', [ProdServEquipController::class, 'deleteService'])->name('services.destroy');

// Equipment routes
Route::post('/equipment', [ProdServEquipController::class, 'storeEquipment'])->name('equipment.store');
Route::put('/equipment/{id}', [ProdServEquipController::class, 'updateEquipment'])->name('equipment.update');
Route::delete('/equipment/{id}', [ProdServEquipController::class, 'deleteEquipment'])->name('equipment.destroy');

// Inventory route
Route::put('/inventory/{id}', [ProdServEquipController::class, 'updateInventory'])->name('inventory.update');
// In your routes/web.php
Route::put('/inventory/update-stock/{id}', [ProdServEquipController::class, 'updateStock'])->name('inventory.updateStock');
Route::put('/inventory/update-damage/{id}', [ProdServEquipController::class, 'updateDamage'])->name('inventory.updateDamage');

Route::get('/products/{id}/view', [ProdServEquipController::class, 'viewProduct'])->name('products.view');
Route::get('/services/{id}/view', [ProdServEquipController::class, 'viewService'])->name('services.view');
Route::get('/equipment/{id}/view', [ProdServEquipController::class, 'viewEquipment'])->name('equipment.view');
Route::get('/inventory/{id}/history', [ProdServEquipController::class, 'viewInventoryHistory'])->name('inventory.history');

use App\Http\Controllers\MedicalManagementController;

// In routes/web.php, add this line with your other medical management routes:

Route::prefix('medical-management')->group(function () {
    // Main unified medical management route
    Route::get('/', [MedicalManagementController::class, 'index'])->name('medical.index');
    
    // Appointment routes
    Route::post('/appointments', [MedicalManagementController::class, 'storeAppointment'])->name('medical.appointments.store');
    
    Route::put('/appointments/{appointment}', [MedicalManagementController::class, 'updateAppointment'])->name('medical.appointments.update');
    Route::delete('/appointments/{id}', [MedicalManagementController::class, 'destroyAppointment'])->name('medical.appointments.destroy');
    Route::get('/appointments/{id}/details', [MedicalManagementController::class, 'getAppointmentDetails'])->name('medical.appointments.details');
    Route::get('/appointments/{id}', [MedicalManagementController::class, 'showAppointment'])->name('medical.appointments.show');

    // Prescription routes
    Route::post('/prescriptions', [MedicalManagementController::class, 'storePrescription'])->name('medical.prescriptions.store');
    Route::get('/prescriptions/{id}/edit', [MedicalManagementController::class, 'editPrescription'])->name('medical.prescriptions.edit');
    Route::put('/prescriptions/{id}', [MedicalManagementController::class, 'updatePrescription'])->name('medical.prescriptions.update');
    Route::delete('/prescriptions/{id}', [MedicalManagementController::class, 'destroyPrescription'])->name('medical.prescriptions.destroy');
    Route::get('/prescriptions/search-products', [MedicalManagementController::class, 'searchProducts'])->name('medical.prescriptions.search-products');
    Route::get('/prescriptions/{id}/print', [MedicalManagementController::class, 'printPrescription'])->name('medical.prescriptions.print');

    // Referral routes - ADD THE MISSING GET ROUTE
    Route::get('/referrals', [MedicalManagementController::class, 'index'])->name('medical.referrals.index'); // ADD THIS LINE
    Route::post('/referrals', [MedicalManagementController::class, 'storeReferral'])->name('medical.referrals.store');
    Route::get('/referrals/{id}/edit', [MedicalManagementController::class, 'editReferral'])->name('medical.referrals.edit');
    Route::put('/referrals/{id}', [MedicalManagementController::class, 'updateReferral'])->name('medical.referrals.update');
    Route::get('/referrals/{id}', [MedicalManagementController::class, 'showReferral'])->name('medical.referrals.show');
    Route::delete('/referrals/{id}', [MedicalManagementController::class, 'destroyReferral'])->name('medical.referrals.destroy');

    // In your routes file (web.php or wherever your medical routes are)
Route::get('/medical-management/appointments/{id}/for-prescription', [MedicalManagementController::class, 'getAppointmentForPrescription'])
    ->name('medical.appointments.for-prescription');

});



// Branch

Route::get('/branch', [BranchController::class, 'index'])->name('branch-index');
Route::get('/branches', [BranchController::class, 'index'])->name('branches-index');
Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
Route::put('/branches/{id}', [BranchController::class, 'update'])->name('branch-update');
Route::get('/branches/{id}', [BranchController::class, 'show'])->name('branches.show');
Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->name('branches-destroy');

//Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->name('branches.destroy');
Route::get('/branch/switch/{id}', [BranchController::class, 'switch'])->name('branch.switch');
Route::get('/switch-branch/{id}', function ($id) {
    Session::put('selected_branch_id', $id);
    return redirect()->back();
})->name('branch.switch');


//Owner
Route::get('/owners', [OwnerController::class, 'index'])->name('owners-index');
Route::post('/owners/store', [OwnerController::class, 'store'])->name('owners.store');
Route::post('/owners', [OwnerController::class, 'store'])->name('owners.store');
Route::put('/owners/{id}', [OwnerController::class, 'update'])->name('owners.update');
Route::delete('/owners/{id}', [OwnerController::class, 'destroy'])->name('owners.destroy');

// Pets
Route::get('/pets', [PetController::class, 'index'])->name('pets-index');
//Route::post('/pets/store', [PetController::class, 'store'])->name('pets.store');
Route::post('/pets', [PetController::class, 'store'])->name('pets.store');

Route::put('/pets/{id}', [PetController::class, 'update'])->name('pets.update');
Route::get('/pets/{id}', [PetController::class, 'show'])->name('pets.show');
Route::delete('/pets/{id}', [PetController::class, 'destroy'])->name('pets.destroy');




// Appointments
Route::get('/appointment', [AppointmentController::class, 'index'])->name('appointments-index');  
Route::post('/appointment', [AppointmentController::class, 'store'])->name('appointments.store');
Route::put('/appointment/{id}', [AppointmentController::class, 'update'])->name('appointments.update');
Route::get('/appointment/{id}', [AppointmentController::class, 'show'])->name('appointments.show');
Route::get('/appointment/{id}', [AppointmentController::class, 'refer'])->name('appointments.refer');
Route::delete('/appointment/{id}', [AppointmentController::class, 'destroy'])->name('appointments.destroy');


// Products
//Route::get('/product', [ProductController::class, 'index'])->name('product-index');
//Route::delete('/product/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
//Route::post('/product', [ProductController::class, 'store'])->name('products.store');
//Route::put('/product/{id}', [ProductController::class, 'update'])->name('products.update');
//Route::put('/products/inventory/update', [ProductController::class, 'updateInventory'])
 //   ->name('products.inventory.update');

//Inventory
//Route::put('/products/{id}/inventory', [ProductController::class, 'update'])->name('products.inventory.update');

//Route::put('/productAndServices/{id}', [ProductAndServicesController::class, 'update'])->name('item.update');
//Route::delete('/productAndServices/{id}', [ProductAndServicesController::class, 'destroy'])->name('item.destroy');

//Services
//Route::resource('services', ServiceController::class);
//Route::get('/services', [ServiceController::class, 'index'])->name('services-index');
//Route::post('/services/store', [ServiceController::class, 'store'])->name('services.store');Route::put('/services/update/{id}', [ServiceController::class, 'update'])->name('services.update');
//Route::delete('/services/{id}', [ServiceController::class, 'destroy'])->name('services.destroy');

//Referral
Route::get('/referral', [ReferralController::class, 'index'])->name('referral-index');
Route::post('/referral', [ReferralController::class, 'store'])->name('referrals.store');
Route::put('/referral/{id}', [ReferralController::class, 'update'])->name('referral.update');
Route::get('/referral/{id}', [ReferralController::class, 'show'])->name('referral.show');
Route::delete('/referral/{id}', [ReferralController::class, 'destroy'])->name('referrals.destroy');

// Sales
Route::get('/sales', [OrderController::class, 'index'])->name('sales-index');
Route::put('/sales/{id}', [OrderController::class, 'update'])->name('sales.update');

// Inventory
//Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory-index');
//Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('inventory.update');
//Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

//activities
//Route::get('/activities', [ActivitiesController::class, 'index'])->name('activities-index');
//Route::post('/activities/store', [ActivitiesController::class, 'store'])->name('activities.store');
//Route::put('/activities/{id}', [ActivitiesController::class, 'update'])->name('activities.update');
//Route::delete('/activities/{id}', [ActivitiesController::class, 'destroy'])->name('activities.destroy');

//Reports
Route::get('/report', [ReportController::class, 'index'])->name('report.index');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
//Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export-pdf');
// In routes/web.php or your routes file
Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('/reports/{reportType}/{recordId}/view', [ReportController::class, 'viewRecord'])->name('reports.viewRecord');
Route::get('/reports/{reportType}/{recordId}', [ReportController::class, 'viewRecord'])->name('reports.view');

//billing
Route::get('/billings', [BillingController::class, 'index'])->name('billing-index');
//Route::post('/billings/store', [BillingController::class, 'store'])->name('billings.store');
Route::put('/billings/{id}', [BillingController::class, 'update'])->name('billings.update');
Route::delete('/billings/{id}', [BillingController::class, 'destroy'])->name('billings.destroy');
Route::post('/billing/pay/{bill}', [BillingController::class, 'payBilling'])->name('billing.pay');

//Prescription
//Route::resource('prescriptions', PrescriptionController::class);
//Route::get('/prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
//Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');

Route::get('/prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
Route::get('/prescriptions/{id}/edit', [PrescriptionController::class, 'edit'])->name('prescriptions.edit');
Route::put('/prescriptions/{id}', [PrescriptionController::class, 'update'])->name('prescriptions.update');
Route::delete('/prescriptions/{id}', [PrescriptionController::class, 'destroy'])->name('prescriptions.destroy');
Route::get('/products/search', [PrescriptionController::class, 'searchProducts'])->name('prescriptions.search-products');



Route::get('/orders', [OrderController::class, 'index'])->name('order-index');
Route::get('/orders/transaction/{paymentId}', [OrderController::class, 'show'])->name('orders.transaction.show');
Route::get('/orders/transaction/{paymentId}/print', [OrderController::class, 'printReceipt'])->name('orders.print-receipt');

// Individual order routes (if you still need them)
Route::get('/orders/order/{orderId}', [OrderController::class, 'showOrder'])->name('orders.order.show');

// Export and API routes
Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');
Route::get('/orders/sales-summary', [OrderController::class, 'salesSummary'])->name('orders.sales-summary');
Route::get('/orders/daily-sales', [OrderController::class, 'dailySales'])->name('orders.daily-sales');
Route::get('/orders/top-products', [OrderController::class, 'topProducts'])->name('orders.top-products');
Route::get('/orders/daily-sales', [OrderController::class, 'dailySales'])->name('orders.daily-sales');
Route::get('/orders/top-products', [OrderController::class, 'topProducts'])->name('orders.top-products');
Route::get('/orders/export', [OrderController::class, 'export'])->name('orders.export');

Route::group(['prefix' => 'pet-management', 'as' => 'pet-management.'], function () {
    // Main index page
    Route::get('/', [PetManagementController::class, 'index'])->name('index');
    
    // Pet routes
    Route::post('/pets', [PetManagementController::class, 'storePet'])->name('storePet');
    Route::put('/pets/{id}', [PetManagementController::class, 'updatePet'])->name('updatePet');
    Route::delete('/pets/{id}', [PetManagementController::class, 'destroyPet'])->name('destroyPet');
    
    // Owner routes
    Route::post('/owners', [PetManagementController::class, 'storeOwner'])->name('storeOwner');
    Route::put('/owners/{id}', [PetManagementController::class, 'updateOwner'])->name('updateOwner');
    Route::delete('/owners/{id}', [PetManagementController::class, 'destroyOwner'])->name('destroyOwner');
    
    // Medical History routes
    Route::post('/medical-history', [PetManagementController::class, 'storeMedicalHistory'])->name('storeMedicalHistory');
    Route::put('/medical-history/{id}', [PetManagementController::class, 'updateMedicalHistory'])->name('updateMedicalHistory');
    Route::delete('/medical-history/{id}', [PetManagementController::class, 'destroyMedicalHistory'])->name('destroyMedicalHistory');

// Add these routes to your web.php file
Route::get('/pet-management/owner-details/{id}', [App\Http\Controllers\PetManagementController::class, 'getOwnerDetails']);
Route::get('/pet-management/pet-details/{id}', [App\Http\Controllers\PetManagementController::class, 'getPetDetails']);
Route::get('/pet-management/medical-details/{id}', [App\Http\Controllers\PetManagementController::class, 'getMedicalDetails']);
// Add these routes to your routes/web.php file
Route::get('/api/owner-details/{id}', [App\Http\Controllers\PetManagementController::class, 'getOwnerDetails']);
Route::get('/api/pet-details/{id}', [App\Http\Controllers\PetManagementController::class, 'getPetDetails']);
Route::get('/api/medical-details/{id}', [App\Http\Controllers\PetManagementController::class, 'getMedicalDetails']);
    
});



Route::prefix('branch-user-management')->name('branch-user-management.')->group(function () {
    Route::get('/', [BranchUserManagementController::class, 'index'])->name('index');
    
    // Branch routes
    Route::post('/branches', [BranchUserManagementController::class, 'storeBranch'])->name('storeBranch');
    Route::put('/branches/{id}', [BranchUserManagementController::class, 'updateBranch'])->name('updateBranch');
    Route::delete('/branches/{id}', [BranchUserManagementController::class, 'destroyBranch'])->name('destroyBranch');
    
    // User routes
    Route::post('/users', [BranchUserManagementController::class, 'storeUser'])->name('storeUser');
    Route::put('/users/{id}', [BranchUserManagementController::class, 'updateUser'])->name('updateUser');
    Route::delete('/users/{id}', [BranchUserManagementController::class, 'destroyUser'])->name('destroyUser');
    
    // Branch switching (if needed)
    Route::post('/switch-branch/{id}', [BranchUserManagementController::class, 'switchBranch'])->name('switchBranch');
    // Add these routes for the new functionality

});

Route::post('/user-management/add-to-branch', [BranchManagementController::class, 'addUserToBranch'])->name('userManagement.addToBranch');
Route::get('/branches/{id}/complete-data', [BranchManagementController::class, 'getCompleteData']);

Route::get('/branch-management', [BranchManagementController::class, 'index'])->name('branch-management.index');

// Branch routes
//Route::post('/branches', [BranchManagementController::class, 'storeBranch'])->name('branches.store');
//Route::put('/branches/{id}', [BranchManagementController::class, 'updateBranch'])->name('branches.update');
//Route::delete('/branches/{id}', [BranchManagementController::class, 'destroyBranch'])->name('branches-destroy');
//Route::post('/branches/switch/{id}', [BranchManagementController::class, 'switchBranch'])->name('branches.switch');
Route::get('/branches/{id}/complete-data', [BranchManagementController::class, 'getCompleteData']);

// User routes
Route::post('/user-management', [BranchManagementController::class, 'storeUser'])->name('userManagement.store');
Route::put('/user-management/{id}', [BranchManagementController::class, 'updateUser'])->name('userManagement.update');
Route::delete('/user-management/{id}', [BranchManagementController::class, 'destroyUser'])->name('userManagement.destroy');

// Sales Management Routes
Route::get('/sales-management', [SalesManagementController::class, 'index'])->name('sales.index');
Route::delete('/sales/billing/{id}', [SalesManagementController::class, 'destroyBilling'])->name('sales.destroyBilling');
Route::post('/sales/billing/{id}/mark-paid', [SalesManagementController::class, 'markAsPaid'])->name('sales.markAsPaid');
Route::get('/sales/transaction/{id}', [SalesManagementController::class, 'showTransaction'])->name('sales.transaction');
Route::get('/sales/print-transaction/{id}', [SalesManagementController::class, 'printTransaction'])->name('sales.printTransaction');
Route::get('/sales/export', [SalesManagementController::class, 'export'])->name('sales.export');

use App\Http\Controllers\SMSSettingsController;
Route::get('/sms-settings', [SMSSettingsController::class, 'index'])->name('sms-settings.index');
Route::put('/sms-settings', [SMSSettingsController::class, 'update'])->name('sms-settings.update');
Route::post('/sms-settings/test', [SMSSettingsController::class, 'testSMS'])->name('sms-settings.test');
