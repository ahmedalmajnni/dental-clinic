<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AppointmentRequestController extends Controller
{
    // =====================================================================
    //  PATIENT SIDE
    // =====================================================================

    public function create()
    {
        return view('appointment_requests.create', [
            'doctors' => Employee::with('branch')->where('job_title', 'doctor')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        // The branch is the doctor's own specialty/clinic — taken from the chosen
        // doctor, never picked separately, so a cosmetic doctor can never be
        // paired with the surgery branch, etc.
        $doctor = Employee::find($request->input('doctor_id'));
        if (! $doctor) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a doctor.']);
        }

        AppointmentRequest::create([
            'patient_id' => Auth::user()->patient_id,
            'doctor_id' => $doctor->id,
            'branch_id' => $doctor->branch_id,
            'preferred_date' => $request->input('preferred_date') ?: null,
            'note' => $request->input('note') ?: null,
            'status' => 'pending',
        ]);

        return redirect()->route('my-requests')->with('flash', ['type' => 'success', 'message' => 'Your request was submitted. The clinic will confirm a time soon — check back here.']);
    }

    public function mine()
    {
        $requests = AppointmentRequest::with(['doctor', 'branch', 'appointment'])
            ->where('patient_id', Auth::user()->patient_id)
            ->orderByDesc('created_at')->get();

        return view('appointment_requests.mine', compact('requests'));
    }

    public function cancel(AppointmentRequest $appointmentRequest)
    {
        // A patient may only cancel their own, still-pending request.
        abort_unless($appointmentRequest->patient_id === Auth::user()->patient_id, 403);
        if ($appointmentRequest->status === 'pending') {
            $appointmentRequest->update(['status' => 'cancelled']);
        }

        return redirect()->route('my-requests')->with('flash', ['type' => 'success', 'message' => 'Request cancelled.']);
    }

    // =====================================================================
    //  STAFF SIDE (admin + employee)
    // =====================================================================

    /**
     * A request belongs to the doctor it was booked with: only that doctor and
     * the admin may see or act on it — never another doctor.
     */
    private function ensureCanHandle(AppointmentRequest $appointmentRequest): void
    {
        $user = Auth::user();
        if (! $user->seesAllRequests() && $appointmentRequest->doctor_id !== $user->employee_id) {
            abort(403, 'This request was booked with another doctor.');
        }
    }

    public function queue()
    {
        $user = Auth::user();
        $query = AppointmentRequest::with(['patient', 'doctor', 'branch'])
            ->orderByRaw("status = 'pending' DESC")   // pending first
            ->orderByDesc('created_at');

        // Admin and reception see every request; a doctor sees only their own.
        if (! $user->seesAllRequests()) {
            $query->where('doctor_id', $user->employee_id);
        }

        return view('requests.index', ['requests' => $query->get()]);
    }

    public function process(AppointmentRequest $appointmentRequest)
    {
        $this->ensureCanHandle($appointmentRequest);
        $appointmentRequest->load(['patient', 'doctor', 'branch', 'appointment']);

        return view('requests.process', compact('appointmentRequest'));
    }

    // Approve: create the real appointment, link it, and record the response.
    public function schedule(Request $request, AppointmentRequest $appointmentRequest)
    {
        $this->ensureCanHandle($appointmentRequest);
        if (! $request->input('scheduled_at')) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Choose a date and time for the appointment.']);
        }

        DB::transaction(function () use ($request, $appointmentRequest) {
            $appointment = Appointment::create([
                'patient_id' => $appointmentRequest->patient_id,
                'doctor_id' => $appointmentRequest->doctor_id,
                'branch_id' => $appointmentRequest->branch_id,
                'scheduled_at' => $request->input('scheduled_at'),
                'status' => 'booked',
            ]);
            $appointmentRequest->update([
                'status' => 'scheduled',
                'appointment_id' => $appointment->id,
                'response_note' => $request->input('response_note') ?: null,
                'processed_by' => Auth::user()->employee_id,
                'processed_at' => now(),
            ]);
        });

        return redirect()->route('requests.index')->with('flash', ['type' => 'success', 'message' => 'Appointment scheduled — the patient can now see the time in their account.']);
    }

    // Decline with a note back to the patient.
    public function decline(Request $request, AppointmentRequest $appointmentRequest)
    {
        $this->ensureCanHandle($appointmentRequest);
        $appointmentRequest->update([
            'status' => 'declined',
            'response_note' => $request->input('response_note') ?: null,
            'processed_by' => Auth::user()->employee_id,
            'processed_at' => now(),
        ]);

        return redirect()->route('requests.index')->with('flash', ['type' => 'success', 'message' => 'Request declined — the patient will see your note.']);
    }
}
