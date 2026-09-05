<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Appointment;
use App\Services\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    private const TYPES = ['xray', 'scan', 'photo'];

    private function formData(): array
    {
        return [
            'appointments' => Appointment::with(['patient', 'doctor'])
                ->orderByDesc('scheduled_at')
                ->get(),
            'types' => self::TYPES,
        ];
    }

    public function index()
    {
        $media = Media::with('patient')->orderByDesc('taken_at')->get();

        return view('media.index', compact('media'));
    }

    public function create()
    {
        return view('media.form', array_merge($this->formData(), [
            'item' => new Media(), 'action' => route('media.store'), 'method' => 'POST',
        ]));
    }

    public function store(Request $request)
    {
        $appointment = $this->appointment($request);
        if (! $appointment) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a visit.']);
        }

        $media = Media::create($this->data($request, $appointment));
        Billing::addClinicalCharge($media->patient_id, 'media_id', $media->id, 'Media: '.$media->type, (float) $media->cost);

        return redirect()->route('media.index')->with('flash', ['type' => 'success', 'message' => 'Media added.']);
    }

    public function edit(Media $medium)
    {
        return view('media.form', array_merge($this->formData(), [
            'item' => $medium, 'action' => route('media.update', $medium), 'method' => 'PUT',
        ]));
    }

    public function update(Request $request, Media $medium)
    {
        $appointment = $this->appointment($request);
        if (! $appointment) {
            return back()->withInput()->with('flash', ['type' => 'error', 'message' => 'Please choose a visit.']);
        }

        DB::transaction(function () use ($request, $appointment, $medium) {
            Billing::removeClinicalCharge('media_id', $medium->id);
            $medium->update($this->data($request, $appointment));
            Billing::addClinicalCharge($medium->patient_id, 'media_id', $medium->id, 'Media: '.$medium->type, (float) $medium->cost);
        });

        return redirect()->route('media.index')->with('flash', ['type' => 'success', 'message' => 'Media updated.']);
    }

    public function destroy(Media $medium)
    {
        Billing::removeClinicalCharge('media_id', $medium->id);
        $medium->delete();

        return redirect()->route('media.index')->with('flash', ['type' => 'success', 'message' => 'Media deleted.']);
    }

    private function appointment(Request $request): ?Appointment
    {
        return Appointment::whereKey($request->input('appointment_id'))->first();
    }

    private function data(Request $request, Appointment $appointment): array
    {
        return [
            'patient_id' => $appointment->patient_id,
            'type' => $request->input('type'),
            'category' => $request->input('category') ?: null,
            'file_url' => $request->input('file_url'),
            'cost' => max(0, (float) ($request->input('cost') ?: 0)),
            'taken_at' => $request->input('taken_at') ?: now(),
        ];
    }
}
