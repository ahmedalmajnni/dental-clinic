<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Specialty;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
    public function index()
    {
        return view('specialties.index', ['specialties' => Specialty::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('specialties.form', [
            'specialty' => new Specialty(), 'action' => route('specialties.store'), 'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        Specialty::create($this->data($request));

        return redirect()->route('specialties.index')->with('flash', ['type' => 'success', 'message' => 'Specialty created.']);
    }

    public function edit(Specialty $specialty)
    {
        return view('specialties.form', [
            'specialty' => $specialty, 'action' => route('specialties.update', $specialty), 'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Specialty $specialty)
    {
        $oldName = $specialty->name;
        $specialty->update($this->data($request));
        Employee::where('specialty', $oldName)->update(['specialty' => $specialty->name]);

        return redirect()->route('specialties.index')->with('flash', ['type' => 'success', 'message' => 'Specialty updated.']);
    }

    public function destroy(Specialty $specialty)
    {
        if (Employee::where('specialty', $specialty->name)->exists()) {
            return redirect()->route('specialties.index')->with('flash', ['type' => 'error', 'message' => 'Cannot delete: this specialty is assigned to an employee.']);
        }

        $specialty->delete();

        return redirect()->route('specialties.index')->with('flash', ['type' => 'success', 'message' => 'Specialty deleted.']);
    }

    private function data(Request $request): array
    {
        return ['name' => trim($request->input('name', ''))];
    }
}
