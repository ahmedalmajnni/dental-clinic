<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LabCaseController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffRegistrationController;
use App\Http\Controllers\TreatmentController;
use Illuminate\Support\Facades\Route;

// ---- Public: login / logout / patient signup ----
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'signup']);
Route::get('/staff-register', [StaffRegistrationController::class, 'create'])->name('staff-register.create');
Route::post('/staff-register', [StaffRegistrationController::class, 'store'])->name('staff-register.store');

// The public front page. Signed-in visitors are bounced to their dashboard.
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');

// ---- Everything below requires login AND an active account (deactivating an
// account takes effect on the person's very next request, not just at their
// next login) ----
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Everyone's own account page — each role edits only their own details.
    Route::get('my-account', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('my-account', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('my-account/password', [ProfileController::class, 'password'])->name('profile.password');

    // Patient self-service: request an appointment and track its status.
    Route::middleware('role:patient')->group(function () {
        Route::get('request-appointment', [AppointmentRequestController::class, 'create'])->name('appointment-request.create');
        Route::post('request-appointment', [AppointmentRequestController::class, 'store'])->name('appointment-request.store');
        Route::get('my-requests', [AppointmentRequestController::class, 'mine'])->name('my-requests');
        Route::post('my-requests/{appointmentRequest}/cancel', [AppointmentRequestController::class, 'cancel'])->name('my-requests.cancel');
    });

    // Admin-only management areas
    Route::middleware('role:admin')->group(function () {
        Route::resource('branches', BranchController::class)->except('show');
        // Employees can no longer be added by hand — they self-register and are
        // approved. We keep listing/editing/removing existing employees.
        Route::resource('employees', EmployeeController::class)->except(['show', 'create', 'store']);

        Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('accounts/requests', [AccountController::class, 'requests'])->name('accounts.requests');
        Route::get('accounts/new', [AccountController::class, 'create'])->name('accounts.create');
        Route::get('accounts/new-staff', [AccountController::class, 'createStaff'])->name('accounts.create-staff');
        Route::post('accounts/staff', [AccountController::class, 'storeStaff'])->name('accounts.store-staff');
        Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('accounts/{account}', [AccountController::class, 'update'])->name('accounts.update');
        Route::put('accounts/{account}/password', [AccountController::class, 'resetPassword'])->name('accounts.password');
        Route::post('accounts/{account}/approve', [AccountController::class, 'approve'])->name('accounts.approve');
        Route::post('accounts/{account}/reject', [AccountController::class, 'reject'])->name('accounts.reject');
        Route::post('accounts/{account}/toggle', [AccountController::class, 'toggle'])->name('accounts.toggle');
    });

    // Staff areas (admin + employee)
    Route::middleware('role:admin,employee')->group(function () {
        // Patient archive (soft delete) — must be declared before the resource.
        Route::get('patients/archived', [PatientController::class, 'archived'])->name('patients.archived');
        Route::post('patients/{id}/restore', [PatientController::class, 'restore'])->name('patients.restore');
        Route::delete('patients/{id}/force', [PatientController::class, 'forceDelete'])->name('patients.force-delete');
        Route::resource('patients', PatientController::class)->except('show');
        Route::resource('appointments', AppointmentController::class)->except('show');
        Route::resource('treatments', TreatmentController::class);
        Route::resource('lab-cases', LabCaseController::class)->except('show')->parameters(['lab-cases' => 'labCase']);
        Route::resource('media', MediaController::class)->except('show');

        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');

        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/new', [PaymentController::class, 'create'])->name('payments.create');
        Route::get('payments/patient/{patient}', [PaymentController::class, 'forPatient'])->name('payments.patient');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');

        // Appointment-request queue (staff process patient requests here).
        Route::get('requests', [AppointmentRequestController::class, 'queue'])->name('requests.index');
        Route::get('requests/{appointmentRequest}', [AppointmentRequestController::class, 'process'])->name('requests.process');
        Route::post('requests/{appointmentRequest}/schedule', [AppointmentRequestController::class, 'schedule'])->name('requests.schedule');
        Route::post('requests/{appointmentRequest}/decline', [AppointmentRequestController::class, 'decline'])->name('requests.decline');

        // Doctor availability. 'slots' comes before the {doctor} wildcard so it is
        // not matched as a doctor id.
        Route::get('availability', [DoctorAvailabilityController::class, 'index'])->name('availability.index');
        Route::get('availability/slots', [DoctorAvailabilityController::class, 'slots'])->name('availability.slots');
        Route::get('availability/{doctor}', [DoctorAvailabilityController::class, 'edit'])->name('availability.edit');
        Route::put('availability/{doctor}', [DoctorAvailabilityController::class, 'update'])->name('availability.update');
        Route::post('availability/{doctor}/time-off', [DoctorAvailabilityController::class, 'storeTimeOff'])->name('availability.time-off.store');
        Route::delete('availability/{doctor}/time-off/{timeOff}', [DoctorAvailabilityController::class, 'destroyTimeOff'])->name('availability.time-off.destroy');
    });
});
