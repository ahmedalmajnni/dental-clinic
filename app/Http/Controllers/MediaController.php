<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Media;
use App\Models\Patient;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    private const TYPES = ['xray', 'scan', 'photo'];

    private function formData(): array
    {
        return [
            'patients' => Patient::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'types' => self::TYPES,
        ];
    }

    public function index()
    {
        $media = Media::with(['patient', 'branch'])->orderByDesc('taken_at')->get();

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
        Media::create($this->data($request));

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
        $medium->update($this->data($request));

        return redirect()->route('media.index')->with('flash', ['type' => 'success', 'message' => 'Media updated.']);
    }

    public function destroy(Media $medium)
    {
        $medium->delete();

        return redirect()->route('media.index')->with('flash', ['type' => 'success', 'message' => 'Media deleted.']);
    }

    private function data(Request $request): array
    {
        return [
            'patient_id' => $request->input('patient_id'),
            'branch_id' => $request->input('branch_id') ?: null,
            'type' => $request->input('type'),
            'category' => $request->input('category') ?: null,
            'file_url' => $request->input('file_url'),
            'taken_at' => $request->input('taken_at') ?: now(),
        ];
    }
}
