<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Employee;
use App\Services\AvailabilityService;
use Carbon\Carbon;
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
            'doctors' => Employee::where('job_title', 'doctor')->orderBy('name')->get(),
        ]);
    }

    public function slots(Request $request, AvailabilityService $availability)
    {
        $doctor = Employee::whereKey($request->query('doctor_id'))
            ->where('job_title', 'doctor')->first();
        if (! $doctor) {
            return response()->json(['slots' => []]);
        }

        $slots = [];
        foreach ($availability->slotsForRange($doctor, now()->startOfDay(), now()->addDays(60)) as $date => $daySlots) {
            foreach ($daySlots as $slot) {
                $slots[] = ['date' => $date, 'time' => $slot->format('H:i')];
            }
        }

        return response()->json(['slots' => $slots]);
    }

    public function store(Request $request, AvailabilityService $availability)
    {
        $doctor = Employee::whereKey($request->input('doctor_id'))
            ->where('job_title', 'doctor')
            ->first();
        if (! $doctor) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a doctor.']);
        }

        $requested = explode('|', (string) $request->input('preferred_slot'), 2);
        $preferredDate = $requested[0] ?? '';
        $preferredTime = $requested[1] ?? '';
        if ($preferredDate === '' || $preferredTime === '') {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose an available time.']);
        }
        try {
            $when = Carbon::createFromFormat('Y-m-d H:i', $preferredDate.' '.$preferredTime);
        } catch (\Throwable $e) {
            $when = null;
        }
        if (! $when || $availability->hasAnyAvailability($doctor) && ! $availability->isBookable($doctor, $when)) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose an available time.']);
        }

        AppointmentRequest::create([
            'patient_id' => Auth::user()->patient_id,
            'doctor_id' => $doctor->id,
            'preferred_date' => $preferredDate,
            'preferred_time' => $preferredTime,
            'note' => $request->input('note') ?: null,
            'status' => 'pending',
        ]);

        return redirect()->route('my-requests')->with('flash', ['type' => 'success', 'message' => 'Your request was submitted. The clinic will confirm a time soon — check back here.']);
    }

    public function mine()
    {
        $requests = AppointmentRequest::with(['doctor', 'appointment'])
            ->where('patient_id', Auth::user()->patient_id)
            ->orderByDesc('created_at')->get();

        return view('appointment_requests.mine', compact('requests'));
    }

    public function cancel(AppointmentRequest $appointmentRequest)
    {
        // A patient may only cancel their own request. A scheduled request's
        // linked appointment must be cancelled as well.
        abort_unless($appointmentRequest->patient_id === Auth::user()->patient_id, 403);
        if (in_array($appointmentRequest->status, ['pending', 'scheduled'], true)) {
            DB::transaction(function () use ($appointmentRequest) {
                if ($appointmentRequest->appointment) {
                    $appointmentRequest->appointment->update(['status' => 'cancelled']);
                }
            $appointmentRequest->update(['status' => 'cancelled']);
            });
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
        $query = AppointmentRequest::with(['patient', 'doctor'])
            ->where('status', 'pending')
            ->orderByDesc('created_at');

        // Admin and reception see every request; a doctor sees only their own.
        if (! $user->seesAllRequests()) {
            $query->where('doctor_id', $user->employee_id);
        }

        return view('requests.index', ['requests' => $query->get()]);
    }

    public function process(AppointmentRequest $appointmentRequest, AvailabilityService $availability)
    {
        $this->ensureCanHandle($appointmentRequest);
        $appointmentRequest->load(['patient', 'doctor', 'appointment']);

        // Two months is far enough ahead for a routine booking without turning the
        // dropdown into a wall of dates.
        $doctor = $appointmentRequest->doctor;
        $hasAvailability = $availability->hasAnyAvailability($doctor);
        $slotsByDate = $hasAvailability
            ? $availability->slotsForRange($doctor, now(), now()->copy()->addDays(60))
            : [];

        return view('requests.process', compact('appointmentRequest', 'slotsByDate', 'hasAvailability'));
    }

    // Approve: create the real appointment, link it, and record the response.
    public function schedule(Request $request, AppointmentRequest $appointmentRequest, AvailabilityService $availability)
    {
        $this->ensureCanHandle($appointmentRequest);
        if (! $request->input('scheduled_at')) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Choose a date and time for the appointment.']);
        }

        // The dropdown only offers free slots, but a stale page or a hand-made POST
        // can still carry a time the doctor is not working — check it server-side.
        // A doctor with no hours on file is exempt, or nobody could book them at all.
        if ($availability->hasAnyAvailability($appointmentRequest->doctor)) {
            try {
                $when = Carbon::parse($request->input('scheduled_at'));
            } catch (\Throwable $e) {
                $when = null;
            }
            if (! $when || ! $availability->isBookable($appointmentRequest->doctor, $when)) {
                return back()->with('flash', ['type' => 'error', 'message' => 'That time is no longer free — pick one of the doctor\'s open slots.']);
            }
        }

        DB::transaction(function () use ($request, $appointmentRequest) {
            $appointment = Appointment::create([
                'patient_id' => $appointmentRequest->patient_id,
                'doctor_id' => $appointmentRequest->doctor_id,
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
        DB::transaction(function () use ($request, $appointmentRequest) {
            if ($appointmentRequest->appointment) {
                $appointmentRequest->appointment->update(['status' => 'cancelled']);
            }
            $appointmentRequest->update([
                'status' => 'declined',
                'response_note' => $request->input('response_note') ?: null,
                'processed_by' => Auth::user()->employee_id,
                'processed_at' => now(),
            ]);
        });

        return redirect()->route('requests.index')->with('flash', ['type' => 'success', 'message' => 'Request declined — the patient will see your note.']);
    }
}
