<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentRequestController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LabCaseController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
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

Route::get('/', fn () => redirect('/dashboard'));

// ---- Everything below requires login ----
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

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
        Route::post('accounts', [AccountController::class, 'store'])->name('accounts.store');
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
        Route::resource('treatments', TreatmentController::class)->except('show');
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
    });
});
