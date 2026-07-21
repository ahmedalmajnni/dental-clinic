<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\LabCase;
use App\Models\Media;
use App\Models\Patient;
use App\Models\Report;
use App\Models\Treatment;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'patient') {
            $pid = $user->patient_id;
            $appointments = Appointment::with(['doctor', 'branch'])
                ->where('patient_id', $pid)->orderByDesc('scheduled_at')->limit(10)->get();
            $treatments = Treatment::where('patient_id', $pid)
                ->orderByDesc('created_at')->limit(10)->get();
            $invoices = Invoice::where('patient_id', $pid)->orderByDesc('created_at')->get();
            $outstanding = $invoices->sum(fn ($i) => (float) $i->balance);
            $requests = AppointmentRequest::with(['doctor', 'branch', 'appointment'])
                ->where('patient_id', $pid)->orderByDesc('created_at')->limit(5)->get();

            return view('dashboard.patient', compact('appointments', 'treatments', 'invoices', 'outstanding', 'requests'));
        }

        $counts = [
            'branch' => Branch::count(),
            'employee' => Employee::count(),
            'patient' => Patient::count(),
            'appointment' => Appointment::count(),
            'treatment' => Treatment::count(),
            'report' => Report::count(),
            'lab_case' => LabCase::count(),
            'media' => Media::count(),
        ];
        $outstanding = (float) Invoice::whereIn('status', ['open', 'partial'])->sum('balance');
        // Admin/reception count every pending request; a doctor counts only theirs.
        $pendingRequestsQuery = AppointmentRequest::where('status', 'pending');
        if (! $user->seesAllRequests()) {
            $pendingRequestsQuery->where('doctor_id', $user->employee_id);
        }
        $pendingRequests = $pendingRequestsQuery->count();
        $pendingStaff = Account::where('role', 'employee')->where('is_active', false)->count();
        $todays = Appointment::with(['patient', 'doctor'])
            ->whereDate('scheduled_at', today())->orderBy('scheduled_at')->get();

        return view('dashboard.staff', compact('counts', 'outstanding', 'pendingRequests', 'pendingStaff', 'todays'));
    }
}
