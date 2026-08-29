<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\DoctorTimeOff;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns a doctor's weekly hours — minus their days off, plus their one-off
 * extra hours, minus what is already booked — into the discrete times a
 * receptionist may actually pick. Every screen that offers a time asks this
 * class, so the rules can never disagree with each other.
 */
class AvailabilityService
{
    /** Free slots on one date, ascending. */
    public function slotsForDate(Employee $doctor, Carbon $date, ?string $ignoreAppointmentId = null): array
    {
        $days = $this->generate($doctor, $date, $date, $ignoreAppointmentId);

        return $days[$date->toDateString()] ?? [];
    }

    /** Free slots per day, keyed 'Y-m-d'. Days with nothing free are left out. */
    public function slotsForRange(Employee $doctor, Carbon $from, Carbon $to, ?string $ignoreAppointmentId = null): array
    {
        return $this->generate($doctor, $from, $to, $ignoreAppointmentId);
    }

    /** True when $when is exactly one of that day's free slots. */
    public function isBookable(Employee $doctor, Carbon $when, ?string $ignoreAppointmentId = null): bool
    {
        foreach ($this->slotsForDate($doctor, $when, $ignoreAppointmentId) as $slot) {
            if ($slot->format('Y-m-d H:i:s') === $when->format('Y-m-d H:i:s')) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyAvailability(Employee $doctor): bool
    {
        return DoctorAvailability::where('doctor_id', $doctor->id)->exists();
    }

    /**
     * The one place slots are built. Everything it needs is loaded up front in
     * three queries, because a month-long range must not turn into a query per
     * day (let alone per slot).
     */
    private function generate(Employee $doctor, Carbon $from, Carbon $to, ?string $ignoreAppointmentId): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();
        if ($to->lt($from)) {
            return [];
        }

        $weekly = DoctorAvailability::where('doctor_id', $doctor->id)->get()->groupBy('weekday');

        $exceptions = DoctorTimeOff::where('doctor_id', $doctor->id)
            ->whereBetween('on_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn ($row) => $row->on_date->format('Y-m-d'));

        $taken = $this->takenTimes($doctor, $from, $to, $ignoreAppointmentId);
        $now = now();

        $days = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $slots = $this->slotsForDay(
                $day,
                $weekly->get($day->dayOfWeek) ?? new Collection(),
                $exceptions->get($day->toDateString()) ?? new Collection(),
                $taken,
                $now
            );
            if ($slots) {
                $days[$day->toDateString()] = $slots;
            }
        }

        return $days;
    }

    /**
     * Times this doctor is already spoken for, as a 'Y-m-d H:i:s' => true set.
     * Cancelled visits free their slot again. Keyed on the wall-clock string
     * rather than the instant so a slot and an appointment read back from the
     * database compare the same way the rest of the app formats them.
     */
    private function takenTimes(Employee $doctor, Carbon $from, Carbon $to, ?string $ignoreAppointmentId): array
    {
        $times = Appointment::where('doctor_id', $doctor->id)
            ->where('status', '<>', 'cancelled')
            ->whereBetween('scheduled_at', [$from, $to->copy()->endOfDay()])
            ->when($ignoreAppointmentId, fn ($q) => $q->where('id', '<>', $ignoreAppointmentId))
            ->pluck('scheduled_at')
            ->map(fn ($at) => $at->format('Y-m-d H:i:s'))
            ->all();

        return array_fill_keys($times, true);
    }

    /** @return Carbon[] */
    private function slotsForDay(Carbon $day, Collection $weekly, Collection $exceptions, array $taken, Carbon $now): array
    {
        $offs = $exceptions->where('kind', 'off');

        // A day off with no times on it means the whole day is gone, whatever
        // the weekly hours or any extra hours say.
        foreach ($offs as $off) {
            if ($this->minutes($off->start_time) === null) {
                return [];
            }
        }

        $windows = [];
        foreach ($weekly as $row) {
            $windows[] = [$row->start_time, $row->end_time, (int) $row->slot_minutes];
        }
        foreach ($exceptions->where('kind', 'extra') as $extra) {
            $windows[] = [$extra->start_time, $extra->end_time, (int) ($extra->slot_minutes ?: 30)];
        }

        $blocked = [];
        foreach ($offs as $off) {
            $blocked[] = [$this->minutes($off->start_time), $this->minutes($off->end_time)];
        }

        $slots = [];
        foreach ($windows as [$start, $end, $length]) {
            $open = $this->minutes($start);
            $close = $this->minutes($end);
            // A zero or negative step would spin forever; nothing guarantees a
            // sane slot_minutes on the exception rows.
            if ($open === null || $close === null || $length < 1) {
                continue;
            }

            for ($at = $open; $at + $length <= $close; $at += $length) {
                if ($this->overlapsBlocked($at, $at + $length, $blocked)) {
                    continue;
                }

                $when = $day->copy()->addMinutes($at);
                $key = $when->format('Y-m-d H:i:s');
                if (isset($taken[$key]) || $when->lt($now)) {
                    continue;
                }

                // Keyed by time, so overlapping windows cannot offer the same
                // slot twice.
                $slots[$key] = $when;
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /** @param array<int, array{0: ?int, 1: ?int}> $blocked */
    private function overlapsBlocked(int $start, int $end, array $blocked): bool
    {
        foreach ($blocked as [$offStart, $offEnd]) {
            if ($offStart === null || $offEnd === null) {
                continue;
            }
            if ($start < $offEnd && $end > $offStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Postgres hands TIME columns back as 'HH:MM:SS' strings; forms post 'HH:MM'.
     * Minutes-from-midnight sidesteps both and makes the arithmetic obvious.
     */
    private function minutes(?string $time): ?int
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $parts = explode(':', trim($time));

        return ((int) $parts[0]) * 60 + (int) ($parts[1] ?? 0);
    }
}
