<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\LabCase;
use App\Models\Media;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Treatment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return $user->role === 'patient'
            ? $this->patientHome($user)
            : $this->staffHome($user);
    }

    /**
     * The patient's home page: what happens next, what they owe, and what has
     * been done for them so far.
     */
    private function patientHome(Account $user)
    {
        $pid = $user->patient_id;
        $now = now();

        $nextAppointment = Appointment::with('doctor')
            ->where('patient_id', $pid)
            ->where('status', 'booked')
            ->where('scheduled_at', '>=', $now)
            ->orderBy('scheduled_at')
            ->first();

        $appointments = Appointment::with('doctor')
            ->where('patient_id', $pid)
            ->orderByDesc('scheduled_at')
            ->limit(6)
            ->get();

        $treatments = Treatment::where('patient_id', $pid)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $invoices = Invoice::where('patient_id', $pid)
            ->orderByDesc('created_at')
            ->get();

        $outstanding = (float) $invoices->sum(fn ($i) => (float) $i->balance);

        $requests = AppointmentRequest::with(['doctor', 'appointment'])
            ->where('patient_id', $pid)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $stats = [
            'upcoming' => Appointment::where('patient_id', $pid)
                ->where('status', 'booked')->where('scheduled_at', '>=', $now)->count(),
            'visits' => Appointment::where('patient_id', $pid)
                ->where('status', 'completed')->count(),
            'treatments' => Treatment::where('patient_id', $pid)
                ->where('status', 'done')->count(),
            'pending_requests' => AppointmentRequest::where('patient_id', $pid)
                ->where('status', 'pending')->count(),
        ];

        $lastPayment = Payment::where('patient_id', $pid)
            ->orderByDesc('paid_at')
            ->first();

        // The doctors this patient has actually been seen by.
        $careTeam = Employee::query()
            ->whereIn('id', Appointment::where('patient_id', $pid)->distinct()->pluck('doctor_id'))
            ->orderBy('name')
            ->get();

        // The most recent clinical note that suggested a follow-up date.
        $nextVisitNote = Report::where('patient_id', $pid)
            ->whereNotNull('next_visit')
            ->orderByDesc('created_at')
            ->first();

        return view('dashboard.patient', compact(
            'nextAppointment', 'appointments', 'treatments', 'invoices', 'outstanding',
            'requests', 'stats', 'lastPayment', 'careTeam', 'nextVisitNote'
        ));
    }

    /**
     * The staff home page. Admin and reception see the whole clinic; a doctor's
     * page is scoped to their own patients and their own day.
     */
    private function staffHome(Account $user)
    {
        $isAdmin = $user->role === 'admin';
        $employee = $user->employee;
        $isDoctor = $employee?->job_title === 'doctor';
        $seesAll = $user->seesAllRequests();   // admin or reception
        $today = today();
        $now = now();

        // Only a doctor gets a narrowed view — admin and reception run the clinic.
        $scopeToDoctor = $isDoctor && ! $seesAll;
        $doctorId = $user->employee_id;

        $todays = Appointment::with(['patient', 'doctor'])
            ->whereDate('scheduled_at', $today)
            ->when($scopeToDoctor, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('scheduled_at')
            ->get();

        $upcoming = Appointment::with(['patient', 'doctor'])
            ->where('scheduled_at', '>', $now)
            ->whereDate('scheduled_at', '<=', $today->copy()->addDays(7))
            ->where('status', 'booked')
            ->when($scopeToDoctor, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get();

        // A doctor counts only the requests booked with them.
        $pendingRequests = AppointmentRequest::where('status', 'pending')
            ->when(! $seesAll, fn ($q) => $q->where('doctor_id', $doctorId))
            ->count();

        $pendingStaff = $isAdmin
            ? Account::where('role', 'employee')->where('is_active', false)->count()
            : 0;

        // Lab work that needs chasing: still open, and due within three days or
        // already overdue.
        $labAttention = LabCase::with(['patient', 'doctor'])
            ->whereIn('status', ['received', 'in_progress', 'ready'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays(3))
            ->when($scopeToDoctor, fn ($q) => $q->where('doctor_id', $doctorId))
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $recentTreatments = Treatment::with('patient')->orderByDesc('created_at')->limit(5)->get();
        $recentPatients = Patient::query()
            ->when($scopeToDoctor, fn ($q) => $q->whereHas('appointments', fn ($appointments) => $appointments->where('doctor_id', $doctorId)))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $counts = [
            'specialty' => \App\Models\Specialty::count(),
            'employee' => Employee::count(),
            'patient' => Patient::count(),
            'appointment' => Appointment::count(),
            'treatment' => Treatment::count(),
            'report' => Report::count(),
            'lab_case' => LabCase::count(),
            'media' => Media::count(),
        ];

        return view('dashboard.staff', compact(
            'isAdmin', 'isDoctor', 'seesAll', 'employee', 'todays', 'upcoming',
            'pendingRequests', 'pendingStaff', 'labAttention', 'recentTreatments',
            'recentPatients', 'counts'
        ));
    }
}
