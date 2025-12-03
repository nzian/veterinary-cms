<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PetManagementController;
use App\Http\Controllers\BranchManagementController;
use App\Http\Controllers\SalesManagementController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\GroomingAgreementController;
use App\Http\Controllers\InitialAssessmentController;
use App\Http\Controllers\BranchUserManagementController;
use App\Http\Controllers\CareContinuityController;
use App\Http\Controllers\MedicalManagementController;
//Register
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\LoginController;
// Reset Pass
use App\Http\Controllers\PasswordResetController;
//Layout
use App\Http\Controllers\AdminController;

// POS Routes
use App\Http\Controllers\POSController;
use App\Http\Controllers\ProdServEquipController;
use App\Http\Controllers\BranchReportController;
use App\Http\Controllers\SMSSettingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\ReferralCompanyController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Models\ReferralCompany;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

// Login 

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');


Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/superadmin/dashboard', [SuperAdminController::class, 'index'])->name('superadmin.dashboard');
});

Route::middleware(['auth', 'isSuperAdmin'])->group(function () {
    Route::get('/superadmin/logins', [DashboardController::class, 'loginAlerts'])->name('superadmin.logins');
    Route::get('/superadmin/stock', [DashboardController::class, 'stockAlerts'])->name('superadmin.stock');
    Route::get('/superadmin/expiration', [DashboardController::class, 'expirationAlerts'])->name('superadmin.expiration');
    Route::get('/superadmin/equipment', [DashboardController::class, 'equipmentStatus'])->name('superadmin.equipment');
});

Route::middleware(['auth'])->group(function () {

    // AJAX: Update appointment status to missed
    Route::post('/appointments/{id}/mark-missed', [\App\Http\Controllers\DashboardController::class, 'markAppointmentMissed'])->name('appointments.markMissed');
    Route::get('/vet/appointments', [MedicalManagementController::class, 'vetAppointments'])->name('vet.appointments');
});



Route::get('/reset-password', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');


Route::get('/admin', [AdminController::class, 'AdminBoard']);
Route::get('/dashboard', [dashboardController::class, 'index'])->name('dashboard-index');


Route::get('/pos', [POSController::class, 'index'])->name('pos');
Route::post('/pos', [POSController::class, 'store'])->name('pos.store'); 
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
  
Route::get('/sales/summary', [OrderController::class, 'salesSummary'])->name('sales.summary');
Route::get('/sales/daily', [OrderController::class, 'dailySales'])->name('sales.daily');
    
Route::middleware(['auth', 'isSuperAdmin'])->group(function () {
    Route::get('/superadmin/dashboard', [DashboardController::class, 'superAdminDashboard'])->name('superadmin.dashboard');
});
Route::middleware(['auth'])->group(function () {
    // Consultation routes
    Route::resource('consultations', ConsultationController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::post('consultations', [ConsultationController::class, 'store'])->name('consultations.store');
    Route::get('consultations/{consultation}/view', [ConsultationController::class, 'view'])->name('consultation.view');
    Route::get('consultations/{consultation}/export', [ConsultationController::class, 'export'])->name('consultation.export');
    Route::get('visits/{visit}/consultation/create', [ConsultationController::class, 'show'])->name('consultation.create');
});
Route::get('/medical-management/pets/details', [MedicalManagementController::class, 'getPetDetails'])->name('medical.pets.details');

// Route for the new health card print function
Route::get('/pet-management/pet/{id}/health-card', [PetManagementController::class, 'healthCard'])->name('pet-management.healthCard');

Route::get('/services/inventory-overview', [ProdServEquipController::class, 'getServiceInventoryOverview'])
    ->name('services.inventory-overview');
// Service-Product Management Routes
Route::get('/services/{service}/products', [ProdServEquipController::class, 'getServiceProducts'])
    ->name('services.products.get');
Route::post('/services/{service}/products', [ProdServEquipController::class, 'updateServiceProducts'])
    ->name('services.products.update');
Route::get('/products/{product}/service-usage', [ProdServEquipController::class, 'getProductServiceUsage'])
    ->name('products.service-usage');
// Service-Product Management Routes
Route::get('/services/{service}/products', [ProdServEquipController::class, 'getServiceProducts'])->name('services.products.get');
Route::post('/services/{service}/products', [ProdServEquipController::class, 'updateServiceProducts'])->name('services.products.update');
Route::get('/prodservequip', [ProdServEquipController::class, 'index'])->name('prodServEquip.index');
Route::get('/prodservequip', [ProdServEquipController::class, 'index'])->name('prodservequip.index');
// routes/web.php

// This single route definition will handle the 'prodServEquip.index' route
Route::resource('prod-serv-equip', App\Http\Controllers\ProdServEquipController::class)->names([
    'index' => 'prodServEquip.index',
    //'store' => 'products.store', // Assuming you use this for products store
    // ... add other necessary route names if needed
]);

// Other routes (make sure to define these too!)
Route::post('/services/{serviceId}/products', [App\Http\Controllers\ProdServEquipController::class, 'updateServiceProducts']);
Route::get('/services/{serviceId}/products', [App\Http\Controllers\ProdServEquipController::class, 'getServiceProducts']);
Route::get('/services/{serviceId}/equipment', [App\Http\Controllers\ProdServEquipController::class, 'getServiceEquipment']);
Route::post('/services/{serviceId}/equipment', [App\Http\Controllers\ProdServEquipController::class, 'updateServiceEquipment']);
Route::get('/equipment/by-branch', [App\Http\Controllers\ProdServEquipController::class, 'getEquipmentByBranch']);
Route::get('/equipment/assignment-history', [App\Http\Controllers\ProdServEquipController::class, 'getEquipmentAssignmentHistory']);
Route::get('/services/inventory-overview', [App\Http\Controllers\ProdServEquipController::class, 'getServiceInventoryOverview']);
Route::get('/services/usage-history', [App\Http\Controllers\ProdServEquipController::class, 'getServiceUsageHistory']);
Route::get('/products/stock-movement-history', [App\Http\Controllers\ProdServEquipController::class, 'getAllStockMovementHistory']);
Route::get('/products/{id}/service-usage', [App\Http\Controllers\ProdServEquipController::class, 'getProductServiceUsage']);
Route::get('/products/{id}/view', [App\Http\Controllers\ProdServEquipController::class, 'viewProduct']);
Route::get('/products/{id}/details-for-service', [App\Http\Controllers\ProdServEquipController::class, 'getProductDetailsForService'])->name('products.detailsForService');
Route::get('/products/consumable-by-filter', [App\Http\Controllers\ProdServEquipController::class, 'getConsumableProductsByFilter'])->name('products.consumableByFilter');
Route::get('/services/{id}/view', [App\Http\Controllers\ProdServEquipController::class, 'viewService']);
Route::get('/equipment/{id}/view', [App\Http\Controllers\ProdServEquipController::class, 'viewEquipment']);
Route::get('/inventory/{id}/history', [App\Http\Controllers\ProdServEquipController::class, 'viewInventoryHistory']);

// Explicitly name the store/update/delete routes for clarity, or update the Route::resource above:
Route::post('products', [App\Http\Controllers\ProdServEquipController::class, 'storeProduct'])->name('products.store');
Route::put('products/{id}', [App\Http\Controllers\ProdServEquipController::class, 'updateProduct'])->name('products.update');
Route::delete('products/{id}', [App\Http\Controllers\ProdServEquipController::class, 'deleteProduct'])->name('products.destroy');

Route::post('services', [App\Http\Controllers\ProdServEquipController::class, 'storeService'])->name('services.store');
Route::put('services/{id}', [App\Http\Controllers\ProdServEquipController::class, 'updateService'])->name('services.update');
Route::delete('services/{id}', [App\Http\Controllers\ProdServEquipController::class, 'deleteService'])->name('services.destroy');

Route::post('equipment', [App\Http\Controllers\ProdServEquipController::class, 'storeEquipment'])->name('equipment.store');
Route::put('equipment/{id}', [App\Http\Controllers\ProdServEquipController::class, 'updateEquipment'])->name('equipment.update');
Route::delete('equipment/{id}', [App\Http\Controllers\ProdServEquipController::class, 'deleteEquipment'])->name('equipment.destroy');

// Service Equipment Routes (for Boarding services)
Route::get('/services/{serviceId}/equipment', [App\Http\Controllers\ProdServEquipController::class, 'getServiceEquipment'])->name('services.equipment.get');
Route::post('/services/{serviceId}/equipment', [App\Http\Controllers\ProdServEquipController::class, 'updateServiceEquipment'])->name('services.equipment.update');
Route::get('/equipment/by-branch', [App\Http\Controllers\ProdServEquipController::class, 'getEquipmentByBranch'])->name('equipment.byBranch');

Route::put('inventory/update-stock/{id}', [App\Http\Controllers\ProdServEquipController::class, 'updateStock'])->name('inventory.updateStock');
Route::put('inventory/update-damage/{id}', [App\Http\Controllers\ProdServEquipController::class, 'updateDamage'])->name('inventory.updateDamage');
Route::get('products/{id}/stock-batches', [App\Http\Controllers\ProdServEquipController::class, 'getStockBatches'])->name('products.stockBatches');

Route::put('/equipment/{id}/update-status', [App\Http\Controllers\ProdServEquipController::class, 'updateEquipmentStatus'])->name('equipment.updateStatus');

// Manufacturer Routes
Route::get('manufacturers', [App\Http\Controllers\ProdServEquipController::class, 'getManufacturers'])->name('manufacturers.index');
Route::post('manufacturers', [App\Http\Controllers\ProdServEquipController::class, 'storeManufacturer'])->name('manufacturers.store');
Route::put('manufacturers/{id}', [App\Http\Controllers\ProdServEquipController::class, 'updateManufacturer'])->name('manufacturers.update');
Route::delete('manufacturers/{id}', [App\Http\Controllers\ProdServEquipController::class, 'deleteManufacturer'])->name('manufacturers.destroy');

Route::get('medical-management/services/{serviceId}/products', [ProdServEquipController::class, 'getServiceProductsForVaccination'])->name('medical.services.products');

// Route to save the specific vaccine details to the pivot table


Route::prefix('medical-management')->group(function () {
    Route::get('/', [MedicalManagementController::class, 'index'])->name('medical.index');
Route::put('appointments/{appointmentId}/record-vaccine-details', [MedicalManagementController::class, 'recordVaccineDetails'])
        ->name('appointments.record-vaccine-details');

    // 2. Route for AJAX fetching products (used in the modal)
    Route::get('services/{serviceId}/products', [MedicalManagementController::class, 'getServiceProductsForVaccination'])
        ->name('services.products'); // Appointment routes
    Route::post('/appointments', [MedicalManagementController::class, 'storeAppointment'])->name('medical.appointments.store'); 
    Route::put('/appointments/{appointment}', [MedicalManagementController::class, 'updateAppointment'])->name('medical.appointments.update');
    Route::delete('/appointments/{id}', [MedicalManagementController::class, 'destroyAppointment'])->name('medical.appointments.destroy');
    Route::get('/appointments/{id}/details', [MedicalManagementController::class, 'getAppointmentDetails'])->name('medical.appointments.details');
    Route::get('/appointments/{id}', [MedicalManagementController::class, 'showAppointment'])->name('medical.appointments.show');
    Route::get('/appointments/{id}/view', [MedicalManagementController::class, 'showAppointment'])->name('medical.appointments.show');
    Route::get('prescriptions', [MedicalManagementController::class, 'index'])
    ->name('medical.prescriptions.index');
    Route::post('/medical-management/prescriptions', [MedicalManagementController::class, 'storePrescription']);

    //Route::get('/medical-management/prescriptions', [MedicalManagementController::class, 'prescriptions']);



    // Visit Records endpoints (JSON APIs for Pet Management tab)
    Route::get('/visit-records', [MedicalManagementController::class, 'listVisitRecords'])->name('visit-records.index');
    Route::post('/visit-records', [MedicalManagementController::class, 'storeVisitRecords'])->name('visit-records.store');
    Route::put('/visit-records/{id}/patient-type', [MedicalManagementController::class, 'updateVisitPatientType'])->name('visit-records.update-patient-type');
    Route::delete('/visit-records/{id}', [MedicalManagementController::class, 'destroyVisitRecord'])->name('visit-records.destroy');
    Route::get('/visit-records/owners-with-pets', [MedicalManagementController::class, 'ownersWithPets'])->name('visit-records.owners');
     Route::put('/medical-management/appointments/{id}', [YourController::class, 'update'])->name('medical.appointments.update');
    Route::get('/medical-management/appointments/{id}/view', [MedicalManagementController::class, 'showAppointment'])
    ->name('medical.appointments.show');
    Route::post('/medical-management/appointments', [MedicalManagementController::class, 'storeAppointment'])->name('medical.appointments.store');


    // Prescription routes
    Route::post('/prescriptions', [MedicalManagementController::class, 'storePrescription'])->name('medical.prescriptions.store');
    Route::get('/prescriptions/{id}/edit', [MedicalManagementController::class, 'editPrescription'])->name('medical.prescriptions.edit');
    Route::put('/prescriptions/{id}', [MedicalManagementController::class, 'updatePrescription'])->name('medical.prescriptions.update');
    Route::delete('/prescriptions/{id}', [MedicalManagementController::class, 'destroyPrescription'])->name('medical.prescriptions.destroy');
    Route::get('/prescriptions/search-products', [MedicalManagementController::class, 'searchProducts'])->name('medical.prescriptions.search-products');
    Route::get('/prescriptions/{id}/print', [MedicalManagementController::class, 'printPrescription'])->name('medical.prescriptions.print');
    Route::get('/medical-management/prescriptions/{id}/edit', [MedicalManagementController::class, 'editPrescription'])
    ->name('medical.prescriptions.edit');

    // Boarding display (GET) for medical-management prefix
    Route::get('/visits/{visit}/boarding', [MedicalManagementController::class, 'showBoarding'])->name('medical.visits.boarding.show');

    // Referral routes 
    Route::get('/referrals', [MedicalManagementController::class, 'index'])->name('medical.referrals.index');
    Route::post('/referrals', [MedicalManagementController::class, 'storeReferral'])->name('medical.referrals.store');
    Route::get('/referrals/{id}/edit', [MedicalManagementController::class, 'editReferral'])->name('medical.referrals.edit');
    Route::put('/referrals/{id}', [MedicalManagementController::class, 'updateReferral'])->name('medical.referrals.update');
    Route::get('/referrals/{id}', [MedicalManagementController::class, 'showReferral'])->name('medical.referrals.show');
    Route::delete('/referrals/{id}', [MedicalManagementController::class, 'destroyReferral'])->name('medical.referrals.destroy');
    Route::get('/referrals/{id}/view', [MedicalManagementController::class, 'viewReferral'])->name('medical.referrals.view');
    Route::get('/referrals/{id}/print', [MedicalManagementController::class, 'printReferral'])->name('medical.referrals.print');
    Route::post('/referrals/{id}/create-visit', [MedicalManagementController::class, 'createVisitFromReferral'])->name('medical.referrals.create-visit');

Route::prefix('visits')->group(function () {
    Route::get('/{visitId}/workspace', [VisitWorkspaceController::class, 'index'])
        ->name('visits.workspace');
    
    Route::post('/{visitId}/save', [VisitWorkspaceController::class, 'saveService'])
        ->name('visits.save.service');
    
    Route::post('/{visitId}/complete', [VisitWorkspaceController::class, 'completeVisit'])
        ->name('visits.complete');
});
    // Visits CRUD
    Route::post('/visits', [MedicalManagementController::class, 'storeVisit'])->name('medical.visits.store');
    Route::put('/visits/{visit}', [MedicalManagementController::class, 'updateVisit'])->name('medical.visits.update');
    Route::delete('/visits/{id}', [MedicalManagementController::class, 'destroyVisit'])->name('medical.visits.destroy');
    Route::get('/visits/{id}', [MedicalManagementController::class, 'showVisit'])->name('medical.visits.show');

    // Service Saves
    Route::post('/visits/{visit}/consultation', [MedicalManagementController::class, 'saveConsultation'])->name('medical.visits.consultation.save');
    Route::post('/visits/{visit}/vaccination', [MedicalManagementController::class, 'saveVaccination'])->name('medical.visits.vaccination.save');
    Route::post('/visits/{visit}/deworming', [MedicalManagementController::class, 'saveDeworming'])->name('medical.visits.deworming.save');
    Route::post('/visits/{visit}/grooming', [MedicalManagementController::class, 'saveGrooming'])->name('medical.visits.grooming.save');
    Route::post('/visits/{visit}/boarding', [MedicalManagementController::class, 'saveBoarding'])->name('medical.visits.boarding.save');
    Route::post('/visits/{visit}/diagnostic', [MedicalManagementController::class, 'saveDiagnostic'])->name('medical.visits.diagnostic.save');
    Route::post('/visits/{visit}/surgical', [MedicalManagementController::class, 'saveSurgical'])->name('medical.visits.surgical.save');
    Route::post('/visits/{visit}/emergency', [MedicalManagementController::class, 'saveEmergency'])->name('medical.visits.emergency.save');
    Route::post('/visits/{visit}/agreement', [GroomingAgreementController::class, 'store'])->name('medical.visits.grooming.agreement.store');
    Route::get('/visits/{visit}/agreement/print', [GroomingAgreementController::class, 'print'])
        ->middleware('auth')
        ->name('medical.visits.grooming.agreement.print');
    Route::put('/visits/{visit}/grooming', [MedicalManagementController::class, 'updateGroomingService'])
    ->name('medical.visits.grooming.update');
});

// Restore Attend/Perform Visit route so the Visits table Attend link works
Route::get('/medical-management/visits/{id}/perform', [MedicalManagementController::class, 'performVisit'])->name('medical.visits.perform');
Route::patch('/medical-management/visits/{id}/workflow', [MedicalManagementController::class, 'updateWorkflowStatus'])->name('medical.visits.workflow');



// Branch

Route::get('/branch', [BranchManagementController::class, 'index'])->name('branch-index');
Route::get('/branches', [BranchManagementController::class, 'index'])->name('branches-index');
Route::post('/branches', [BranchManagementController::class, 'storeBranch'])->name('branches.store');
Route::put('/branches/{id}', [BranchManagementController::class, 'updateBranch'])->name('branch-update');
Route::get('/branches/{id}', [BranchManagementController::class, 'show'])->name('branches.show');
Route::delete('/branches/{id}', [BranchManagementController::class, 'destroyBranch'])->name('branches-destroy');


Route::get('/branch/switch/{id}', [BranchManagementController::class, 'switchBranch'])->name('branch.switch');
Route::get('/branch/clear', [BranchManagementController::class, 'clearBranch'])->name('branch.clear');


//Owner
Route::get('/owners', [OwnerController::class, 'index'])->name('owners-index');
Route::post('/owners/store', [OwnerController::class, 'store'])->name('owners.store');
Route::post('/owners', [OwnerController::class, 'store'])->name('owners.store');
Route::put('/owners/{id}', [OwnerController::class, 'update'])->name('owners.update');
Route::delete('/owners/{id}', [OwnerController::class, 'destroy'])->name('owners.destroy');

// Pets
Route::get('/pets', [PetManagementController::class, 'index'])->name('pets-index');
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

//Referral
Route::get('/referral', [ReferralController::class, 'index'])->name('referral-index');
Route::post('/referral', [ReferralController::class, 'store'])->name('referrals.store');
Route::put('/referral/{id}', [ReferralController::class, 'update'])->name('referral.update');
Route::get('/referral/{id}', [ReferralController::class, 'show'])->name('referral.show');
Route::delete('/referral/{id}', [ReferralController::class, 'destroy'])->name('referrals.destroy');

// Sales
Route::get('/sales', [OrderController::class, 'index'])->name('sales-index');
Route::put('/sales/{id}', [OrderController::class, 'update'])->name('sales.update');

//Reports
Route::get('/report', [ReportController::class, 'index'])->name('report.index');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('/reports/{reportType}/{recordId}/view', [ReportController::class, 'viewRecord'])->name('reports.viewRecord');
Route::get('/reports/{reportType}/{recordId}', [ReportController::class, 'viewRecord'])->name('reports.view');
Route::get('/reports/{reportType}/{recordId}/pdf', [ReportController::class, 'viewRecordPDF'])->name('reports.viewPDF');
Route::get('/reports/{reportType}/{recordId}/pdf', [ReportController::class, 'generatePDF'])
    ->name('reports.pdf');


Route::middleware(['auth'])->group(function() {
    Route::get('/branch-reports', [BranchReportController::class, 'index'])
        ->name('branch-reports.index');
    
    Route::get('/branch-reports/{type}/{id}', [BranchReportController::class, 'show'])
        ->name('branch-reports.show');
    
    Route::get('/branch-reports/export', [BranchReportController::class, 'export'])
        ->name('branch-reports.export');
Route::get('/branch-reports/{reportType}/{id}/pdf', [BranchReportController::class, 'showDetailedPDF'])->name('branch-reports.pdf');
        });
    

//billing
Route::get('/billings', [BillingController::class, 'index'])->name('billing-index');
Route::put('/billings/{id}', [BillingController::class, 'update'])->name('billings.update');
Route::delete('/billings/{id}', [BillingController::class, 'destroy'])->name('billings.destroy');
Route::post('/billing/pay/{bill}', [BillingController::class, 'payBilling'])->name('billing.pay');
Route::get('/sales/billing/{id}', [SalesManagementController::class, 'showBilling'])->name('sales.billing.show');
Route::get('/sales/billing/{id}/pdf', [SalesController::class, 'generateBillingPDF'])->name('sales.billing.pdf');

Route::get('/api/products/available', [SalesManagementController::class, 'getAvailableProducts'])->middleware('auth');

// Update the existing billing pay route or add if it doesn't exist
Route::post('/sales/billing/{id}/pay', [SalesManagementController::class, 'markAsPaid'])->middleware('auth')->name('sales.billing.pay');
///Route::get('/prescriptions', [PrescriptionController::class, 'index'])->name('prescriptions.index');
//Route::post('/prescriptions', [PrescriptionController::class, 'store'])->name('prescriptions.store');
//Route::get('/prescriptions/{id}/edit', [PrescriptionController::class, 'edit'])->name('prescriptions.edit');
//Route::put('/prescriptions/{id}', [PrescriptionController::class, 'update'])->name('prescriptions.update');
//Route::delete('/prescriptions/{id}', [PrescriptionController::class, 'destroy'])->name('prescriptions.destroy');
//Route::get('/products/search', [PrescriptionController::class, 'searchProducts'])->name('prescriptions.search-products');



Route::get('/orders', [OrderController::class, 'index'])->name('order-index');
Route::get('/orders/transaction/{paymentId}', [OrderController::class, 'show'])->name('orders.transaction.show');
Route::get('/orders/transaction/{paymentId}/print', [OrderController::class, 'printReceipt'])->name('orders.print-receipt');
Route::get('/orders/order/{orderId}', [OrderController::class, 'showOrder'])->name('orders.order.show');

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
    
    // Pet CRUD routes
    Route::post('/pets', [PetManagementController::class, 'storePet'])->name('storePet');
    Route::put('/pets/{id}', [PetManagementController::class, 'updatePet'])->name('updatePet');
    Route::delete('/pets/{id}', [PetManagementController::class, 'destroyPet'])->name('destroyPet');
    
    // Owner CRUD routes
    Route::post('/owners', [PetManagementController::class, 'storeOwner'])->name('storeOwner');
    Route::put('/owners/{id}', [PetManagementController::class, 'updateOwner'])->name('updateOwner');
    Route::delete('/owners/{id}', [PetManagementController::class, 'destroyOwner'])->name('destroyOwner');
    
    // Medical History CRUD routes
    Route::post('/medical-history', [PetManagementController::class, 'storeMedicalHistory'])->name('storeMedicalHistory');
    Route::put('/medical-history/{id}', [PetManagementController::class, 'updateMedicalHistory'])->name('updateMedicalHistory');
    Route::delete('/medical-history/{id}', [PetManagementController::class, 'destroyMedicalHistory'])->name('destroyMedicalHistory');
    
    // AJAX routes for enhanced modals
    Route::get('/pet/{id}/details', [PetManagementController::class, 'getPetDetails'])->name('getPetDetails');
    Route::get('/owner/{id}/details', [PetManagementController::class, 'getOwnerDetails'])->name('getOwnerDetails');
    Route::get('/medical/{id}/details', [PetManagementController::class, 'getMedicalDetails'])->name('getMedicalDetails');
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



Route::middleware(['auth'])->group(function () {
    
    // Main Dashboard Route (handles routing based on role and branch mode)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard-index');
    
    // Super Admin Dashboard (Global View - All Branches)
    Route::get('/superadmin/dashboard', [SuperAdminDashboardController::class, 'index'])
        ->name('superadmin.dashboard');
    
    // Super Admin Branch Detail View
    Route::get('/superadmin/branch/{branchId}', [SuperAdminDashboardController::class, 'showBranch'])
        ->name('superadmin.branch.show');
    
    // Branch Management Routes
    Route::get('/branch-management', [BranchManagementController::class, 'index'])
        ->name('branch-management.index');
    
    // Branch Switching Routes
    Route::get('/branch/switch/{id}', [BranchManagementController::class, 'switchBranch'])
        ->name('branch.switch');
    
    Route::get('/branch/clear', [BranchManagementController::class, 'clearBranch'])
        ->name('branch.clear');
    
    // Your other routes...
});

Route::middleware(['auth'])->group(function () {


    Route::get('referral-companies', [ReferralCompanyController::class, 'index'])->name('referralCompany.index');
    Route::post('referral-companies', [ReferralCompanyController::class, 'store'])->name('referralCompany.store');
    Route::get('referral-companies/{id}', [ReferralCompanyController::class, 'show'])->name('referralCompany.show');
    Route::put('referral-companies/{referralCompany}', [ReferralCompanyController::class, 'update'])->name('referralCompany.update');
    Route::delete('referral-companies/{referralCompany}', [ReferralCompanyController::class, 'destroy'])->name('referralCompany.destroy'); 

    // Branch Management
    Route::get('/branch-management', [BranchManagementController::class, 'index'])
        ->name('branch-management.index');
    
    // Branch switching routes
    Route::get('/branch/switch/{id}', [BranchManagementController::class, 'switchBranch'])
        ->name('branch.switch');
    
    Route::get('/branch/clear', [BranchManagementController::class, 'clearBranch'])
        ->name('branch.clear');
    
    Route::post('/user-management/add-to-branch', [BranchManagementController::class, 'addUserToBranch'])->name('userManagement.addToBranch');
Route::get('/branches/{id}/complete-data', [BranchManagementController::class, 'getCompleteData']);

Route::get('/branch-management', [BranchManagementController::class, 'index'])->name('branch-management.index');

Route::get('/branches/{id}/complete-data', [BranchManagementController::class, 'getCompleteData']);

// User routes
Route::post('/user-management', [BranchManagementController::class, 'storeUser'])->name('userManagement.store');
Route::put('/user-management/{id}', [BranchManagementController::class, 'updateUser'])->name('userManagement.update');
Route::delete('/user-management/{id}', [BranchManagementController::class, 'destroyUser'])->name('userManagement.destroy');

});


// Sales Management Routes
Route::get('/sales-management', [SalesManagementController::class, 'index'])->name('sales.index');
Route::delete('/sales/billing/{id}', [SalesManagementController::class, 'destroyBilling'])->name('sales.destroyBilling');
Route::post('/sales/billing/{id}/mark-paid', [SalesManagementController::class, 'markAsPaid'])->name('sales.markAsPaid');
Route::post('/sales/billing-group/mark-paid', [SalesManagementController::class, 'markGroupAsPaid'])->name('sales.markGroupAsPaid');
Route::post('/sales/auto-generate-billings', [SalesManagementController::class, 'autoGenerateGroupedBillings'])->name('sales.auto-generate');
Route::post('/sales/generate-grouped', [SalesManagementController::class, 'generateGroupedBilling'])->name('sales.generate-grouped');
Route::get('/sales/transaction/{id}/json', [SalesManagementController::class, 'showTransactionJson'])->name('sales.transaction.json');
Route::get('/sales/transaction/{id}', [SalesManagementController::class, 'showTransaction'])->name('sales.transaction');
Route::get('/sales/print-transaction/{id}', [SalesManagementController::class, 'printTransaction'])->name('sales.printTransaction');
Route::get('/sales/export', [SalesManagementController::class, 'export'])->name('sales.export');
Route::get('/sales/billing/{id}/receipt', [SalesManagementController::class, 'showReceipt'])->name('sales.billing.receipt');
Route::get('/sales/grouped-billing/receipt/{owner_id}/{bill_date}', [SalesManagementController::class, 'showGroupedReceipt'])->name('sales.grouped.billing.receipt');
Route::post('/sales/billing/{id}/pay', [SalesManagementController::class, 'markAsPaid'])->name('sales.billing.pay');
Route::post('/sales/billing/{billing}/add-product', [SalesManagementController::class, 'addProductToBilling'])->name('sales.billing.addProduct');
Route::post('/sales/billing/{billId}/add-products', [SalesManagementController::class, 'saveProductsToBilling'])->name('sales.billing.addProducts');
Route::get('/sms-settings', [SMSSettingsController::class, 'index'])->name('sms-settings.index');
Route::put('/sms-settings', [SMSSettingsController::class, 'update'])->name('sms-settings.update');
Route::post('/sms-settings/test', [SMSSettingsController::class, 'testSMS'])->name('sms-settings.test');

Route::post('/visits/{visit}/services/{service}/complete', [MedicalManagementController::class, 'completeService'])
    ->name('visits.services.complete')
    ->middleware('auth');

Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');


Route::middleware(['auth'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.get');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAll');
    Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark');
});



// Replace your existing global search route with this:
Route::get('/global-search', [GlobalSearchController::class, 'search'])
    ->name('global.search')
    ->middleware('auth');
//Route::get('/search', [App\Http\Controllers\GlobalSearchController::class, 'index'])->name('global.search');
//Route::get('/search', [App\Http\Controllers\GlobalSearchController::class, 'redirect'])->name('global.search');



// Super Admin Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/superadmin/dashboard', [SuperAdminDashboardController::class, 'index'])
        ->name('superadmin.dashboard');
    
    Route::get('/superadmin/branch/{branchId}', [SuperAdminDashboardController::class, 'showBranch'])
        ->name('superadmin.branch.show');
});

Route::controller(ActivityController::class)->group(function () {
    
    // 1. Sidebar Route (INDEX)
    // Route name: activities.index | URL: /activities-index
    Route::get('/activities-index', 'index')->name('activities.index'); 
    
    // 2. Dynamic View Route (ATTEND)
    // Route name: activities.attend | URL: /activities/{id}
    Route::get('/activities/{id}', 'attendVisit')->name('activities.attend');

    // 3. Dynamic Save Route (SAVE)
    // Route name: activities.save | URL: /activities/{visitId}/save/{activityKey}
    Route::post('/activities/{visitId}/save/{activityKey}', 'handleActivitySave')->name('activities.save');
});

Route::post('/medical/initial-assessments', [InitialAssessmentController::class, 'store'])
    ->name('medical.initial_assessments.store');



// Care Continuity Management Routes
Route::prefix('care-continuity')->name('care-continuity.')->group(function () {
    Route::get('/', [CareContinuityController::class, 'index'])->name('index');
    
    // Follow-up Appointments
    Route::post('/appointments/store', [CareContinuityController::class, 'storeFollowUpAppointment'])->name('care.appointments.store');
    Route::put('/appointments/{id}', [CareContinuityController::class, 'updateFollowUpAppointment'])->name('appointments.update');
    Route::delete('/appointments/{id}', [CareContinuityController::class, 'destroyFollowUpAppointment'])->name('appointments.destroy');
    Route::post('/appointments/{id}/create-visit', [CareContinuityController::class, 'createVisitFromAppointment'])->name('appointments.create-visit');
    
    // Prescriptions
    Route::post('/prescriptions/store', [CareContinuityController::class, 'storeFollowUpPrescription'])->name('prescriptions.store');
    Route::get('/prescriptions/{id}', [CareContinuityController::class, 'showPrescription'])->name('prescriptions.show');
    Route::delete('/prescriptions/{id}', [CareContinuityController::class, 'destroyPrescription'])->name('prescriptions.destroy');
    
    // Referrals
    Route::post('/referrals/store', [CareContinuityController::class, 'storeReferral'])->name('referrals.store');
    Route::get('/referrals/{id}', [CareContinuityController::class, 'showReferral'])->name('referrals.show');
    Route::delete('/referrals/{id}', [CareContinuityController::class, 'destroyReferral'])->name('referrals.destroy');
    Route::post('/referrals/{id}/create-visit', [CareContinuityController::class, 'createVisitFromReferral'])->name('referrals.create-visit');
});