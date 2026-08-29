<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Report;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    private const STATUSES = ['booked', 'completed', 'cancelled', 'no_show'];

    /**
     * A plain doctor only ever deals with their own appointments — they have no
     * relation to what other doctors have booked. Admin and reception (who run
     * the whole clinic) are exempt, same as AppointmentRequestController.
     */
    private function isRestrictedDoctor(): bool
    {
        $user = Auth::user();

        return $user->employee?->job_title === 'doctor' && ! $user->seesAllRequests();
    }

    /**
     * Mirrors AppointmentRequestController::ensureCanHandle() — a restricted
     * doctor may only reach appointments booked with them.
     */
    private function ensureCanHandle(Appointment $appointment): void
    {
        if ($this->isRestrictedDoctor() && $appointment->doctor_id !== Auth::user()->employee_id) {
            abort(403, 'This appointment belongs to another doctor.');
        }
    }

    private function formData(): array
    {
        $restricted = $this->isRestrictedDoctor();

        return [
            'patients' => Patient::orderBy('name')->get(),
            'doctors' => $restricted ? collect([Auth::user()->employee]) : Employee::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'statuses' => self::STATUSES,
        ];
    }

    public function index()
    {
        $restricted = $this->isRestrictedDoctor();

        $appointments = Appointment::with(['patient', 'doctor', 'branch'])
            ->when($restricted, fn ($q) => $q->where('doctor_id', Auth::user()->employee_id))
            ->orderByDesc('scheduled_at')->get();

        return view('appointments.index', ['appointments' => $appointments, 'showDoctor' => ! $restricted]);
    }

    public function create()
    {
        $appointment = new Appointment();
        if ($this->isRestrictedDoctor()) {
            $appointment->doctor_id = Auth::user()->employee_id;
        }

        return view('appointments.form', array_merge($this->formData(), [
            'appointment' => $appointment, 'report' => null,
            'action' => route('appointments.store'), 'method' => 'POST',
        ]));
    }

    public function store(Request $request, AvailabilityService $availability)
    {
        if (! $request->input('scheduled_at')) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a valid date and time.']);
        }
        $data = $this->data($request);
        if ($this->isRestrictedDoctor()) {
            $data['doctor_id'] = Auth::user()->employee_id;
        }
        if ($problem = $this->availabilityProblem($availability, $request, $data, null)) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => $problem]);
        }
        $appointment = Appointment::create($data);
        $this->syncNotes($appointment, $request);

        return redirect()->route('appointments.index')->with('flash', ['type' => 'success', 'message' => 'Appointment booked.']);
    }

    public function edit(Appointment $appointment)
    {
        $this->ensureCanHandle($appointment);

        return view('appointments.form', array_merge($this->formData(), [
            'appointment' => $appointment,
            'report' => Report::where('appointment_id', $appointment->id)->first(),
            'action' => route('appointments.update', $appointment), 'method' => 'PUT',
        ]));
    }

    public function update(Request $request, Appointment $appointment, AvailabilityService $availability)
    {
        $this->ensureCanHandle($appointment);
        $data = $this->data($request);
        if ($this->isRestrictedDoctor()) {
            $data['doctor_id'] = $appointment->doctor_id; // cannot hand their own appointment to another doctor
        }
        if ($problem = $this->availabilityProblem($availability, $request, $data, $appointment)) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => $problem]);
        }
        $appointment->update($data);
        $this->syncNotes($appointment, $request);

        return redirect()->route('appointments.index')->with('flash', ['type' => 'success', 'message' => 'Appointment updated.']);
    }

    /**
     * The real guard on doctor availability — the slot picker in the form is a
     * convenience that a hand-crafted POST walks straight past.
     *
     * Returns the message to show, or null when the booking is allowed.
     */
    private function availabilityProblem(AvailabilityService $availability, Request $request, array $data, ?Appointment $existing): ?string
    {
        // Only the admin gets the override. Reception must not be able to quietly
        // fill a doctor's day off, but the clinic owner sometimes has to squeeze
        // in an emergency, so the escape hatch is theirs alone.
        if (Auth::user()->role === 'admin' && $request->boolean('ignore_availability')) {
            return null;
        }

        // Cancelling frees a slot rather than taking one, so it is never blocked.
        if (($data['status'] ?? null) === 'cancelled') {
            return null;
        }

        $doctor = Employee::find($data['doctor_id']);
        if (! $doctor || ! $doctor->isDoctor() || ! $availability->hasAnyAvailability($doctor)) {
            return null;
        }

        try {
            $when = Carbon::parse($data['scheduled_at']);
        } catch (\Throwable $e) {
            return 'Please choose a valid date and time.';
        }

        // Editing the notes or status of a past visit must not be held hostage by
        // hours that have since changed — only a time that actually moved is checked.
        if ($existing && $existing->doctor_id === $doctor->id
            && optional($existing->scheduled_at)->format('Y-m-d H:i') === $when->format('Y-m-d H:i')) {
            return null;
        }

        if ($availability->isBookable($doctor, $when, $existing?->id)) {
            return null;
        }

        return 'Dr '.$doctor->name.' is not available then — pick one of their open times.';
    }

    /**
     * The doctor's clinical notes now live on the appointment form, but are still
     * stored in the `report` table (one note record per visit) so the database
     * design is unchanged. Creates, updates or clears the note as needed.
     */
    private function syncNotes(Appointment $appointment, Request $request): void
    {
        $fields = [
            'diagnosis' => $request->input('diagnosis') ?: null,
            'notes' => $request->input('notes') ?: null,
            'next_visit' => $request->input('next_visit') ?: null,
        ];
        $report = Report::where('appointment_id', $appointment->id)->first();

        // Nothing filled in — remove any existing note for this visit.
        if (! array_filter($fields)) {
            $report?->delete();

            return;
        }

        $fields += [
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
        ];
        $report ? $report->update($fields) : Report::create($fields);
    }

    public function destroy(Appointment $appointment)
    {
        $this->ensureCanHandle($appointment);
        $appointment->delete();

        return redirect()->route('appointments.index')->with('flash', ['type' => 'success', 'message' => 'Appointment deleted.']);
    }

    private function data(Request $request): array
    {
        return [
            'patient_id' => $request->input('patient_id'),
            'doctor_id' => $request->input('doctor_id'),
            'branch_id' => $request->input('branch_id'),
            'scheduled_at' => $request->input('scheduled_at'),
            'status' => $request->input('status') ?: 'booked',
        ];
    }
}
