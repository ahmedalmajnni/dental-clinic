<?php

namespace App\Http\Controllers;

use App\Models\DoctorAvailability;
use App\Models\DoctorTimeOff;
use App\Models\Employee;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorAvailabilityController extends Controller
{
    // Carbon's dayOfWeek numbering, so the index is the stored weekday value.
    private const WEEKDAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    /**
     * Availability belongs to the doctor who works it: that doctor may edit their
     * own, the admin may edit anyone's. Reception and lab techs never edit hours.
     */
    private function canManage(Employee $doctor): bool
    {
        $user = Auth::user();

        return $user->role === 'admin'
            || ($user->employee_id === $doctor->id && $doctor->isDoctor());
    }

    private function ensureCanManage(Employee $doctor): void
    {
        // Only doctors have hours at all, so a non-doctor id is a bad URL rather
        // than a permission problem.
        if (! $doctor->isDoctor()) {
            abort(404);
        }
        if (! $this->canManage($doctor)) {
            abort(403, 'You can only edit your own availability.');
        }
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return view('availability.index', [
                'doctors' => Employee::with(['branch', 'availability'])
                    ->where('job_title', 'doctor')->orderBy('name')->get(),
                'weekdays' => self::WEEKDAYS,
            ]);
        }

        // A doctor has exactly one page to be on — their own.
        if ($user->employee && $user->employee->isDoctor()) {
            return redirect()->route('availability.edit', $user->employee);
        }

        abort(403, 'Only doctors and the admin manage availability.');
    }

    public function edit(Employee $doctor)
    {
        $this->ensureCanManage($doctor);

        return view('availability.edit', [
            'doctor' => $doctor,
            'rows' => DoctorAvailability::where('doctor_id', $doctor->id)
                ->orderBy('weekday')->orderBy('start_time')->get(),
            // Past exceptions are noise once the date has gone by.
            'timeOff' => DoctorTimeOff::where('doctor_id', $doctor->id)
                ->whereDate('on_date', '>=', now()->toDateString())
                ->orderBy('on_date')->orderBy('start_time')->get(),
            'weekdays' => self::WEEKDAYS,
        ]);
    }

    /**
     * The editor posts the whole weekly grid every time, so the simplest correct
     * save is to replace the doctor's rows wholesale inside one transaction.
     */
    public function update(Request $request, Employee $doctor)
    {
        $this->ensureCanManage($doctor);

        $weekdays = (array) $request->input('weekday', []);
        $starts = (array) $request->input('start_time', []);
        $ends = (array) $request->input('end_time', []);
        $lengths = (array) $request->input('slot_minutes', []);

        $rows = [];
        foreach ($weekdays as $i => $weekday) {
            $start = trim((string) ($starts[$i] ?? ''));
            $end = trim((string) ($ends[$i] ?? ''));

            // A blank line is how the form says "no hours here", not an error.
            if ($start === '' || $end === '') {
                continue;
            }

            $weekday = (int) $weekday;
            $length = (int) ($lengths[$i] ?? 30);

            if ($weekday < 0 || $weekday > 6) {
                return $this->badInput('Pick a day of the week for every row.');
            }
            if ($this->minutes($end) <= $this->minutes($start)) {
                return $this->badInput('Each row must end after it starts.');
            }
            if ($length < 5 || $length > 240) {
                return $this->badInput('Slot length must be between 5 and 240 minutes.');
            }

            $rows[] = [
                'doctor_id' => $doctor->id,
                'weekday' => $weekday,
                'start_time' => $start,
                'end_time' => $end,
                'slot_minutes' => $length,
            ];
        }

        DB::transaction(function () use ($doctor, $rows) {
            DoctorAvailability::where('doctor_id', $doctor->id)->delete();
            foreach ($rows as $row) {
                DoctorAvailability::create($row);
            }
        });

        return redirect()->route('availability.edit', $doctor)
            ->with('flash', ['type' => 'success', 'message' => 'Weekly hours saved.']);
    }

    public function storeTimeOff(Request $request, Employee $doctor)
    {
        $this->ensureCanManage($doctor);

        $date = $this->parseDate($request->input('on_date'));
        if (! $date) {
            return $this->badInput('Choose a valid date.');
        }
        if ($date->lt(now()->startOfDay())) {
            return $this->badInput('That date has already passed.');
        }

        $kind = $request->input('kind') === 'extra' ? 'extra' : 'off';
        $start = trim((string) $request->input('start_time'));
        $end = trim((string) $request->input('end_time'));
        $length = $request->input('slot_minutes');

        if (($start === '') !== ($end === '')) {
            return $this->badInput('Give both a start and an end time, or neither.');
        }
        if ($kind === 'extra' && $start === '') {
            return $this->badInput('Extra hours need a start and an end time.');
        }
        if ($start !== '' && $this->minutes($end) <= $this->minutes($start)) {
            return $this->badInput('The end time must be after the start time.');
        }
        if ($length !== null && $length !== '' && ((int) $length < 5 || (int) $length > 240)) {
            return $this->badInput('Slot length must be between 5 and 240 minutes.');
        }

        DoctorTimeOff::create([
            'doctor_id' => $doctor->id,
            'on_date' => $date->toDateString(),
            'kind' => $kind,
            'start_time' => $start ?: null,
            'end_time' => $end ?: null,
            'slot_minutes' => ($length === null || $length === '') ? null : (int) $length,
            'reason' => $request->input('reason') ?: null,
        ]);

        return redirect()->route('availability.edit', $doctor)->with('flash', [
            'type' => 'success',
            'message' => $kind === 'extra' ? 'Extra hours added.' : 'Time off added.',
        ]);
    }

    public function destroyTimeOff(Employee $doctor, DoctorTimeOff $timeOff)
    {
        $this->ensureCanManage($doctor);
        // Guessing another doctor's exception id must not delete it.
        abort_unless($timeOff->doctor_id === $doctor->id, 404);

        $timeOff->delete();

        return redirect()->route('availability.edit', $doctor)
            ->with('flash', ['type' => 'success', 'message' => 'Exception removed.']);
    }

    /**
     * Feeds the time picker on the staff screens. A bad doctor or date answers
     * with an empty list rather than an error, so the form just shows no times.
     */
    public function slots(Request $request, AvailabilityService $availability)
    {
        $doctor = Employee::find($request->query('doctor_id'));
        $date = $this->parseDate($request->query('date'));

        if (! $doctor || ! $doctor->isDoctor() || ! $date) {
            return response()->json(['slots' => []]);
        }

        $slots = $availability->slotsForDate($doctor, $date, $request->query('ignore') ?: null);

        return response()->json([
            'slots' => array_map(fn (Carbon $slot) => $slot->format('H:i'), $slots),
        ]);
    }

    private function badInput(string $message)
    {
        return back()->withInput()->with('flash', ['type' => 'error', 'message' => $message]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);
        try {
            $date = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        // createFromFormat happily rolls "2026-02-31" over into March; only an
        // exact round-trip proves the date was real.
        return $date->format('Y-m-d') === $value ? $date : null;
    }

    /** Forms post "HH:MM"; minutes-from-midnight makes the comparisons obvious. */
    private function minutes(string $time): int
    {
        $parts = explode(':', trim($time));

        return ((int) $parts[0]) * 60 + (int) ($parts[1] ?? 0);
    }
}
