<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('branches.index', ['branches' => Branch::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('branches.form', [
            'branch' => new Branch(), 'action' => route('branches.store'), 'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        Branch::create($this->data($request));

        return redirect()->route('branches.index')->with('flash', ['type' => 'success', 'message' => 'Branch created.']);
    }

    public function edit(Branch $branch)
    {
        return view('branches.form', [
            'branch' => $branch, 'action' => route('branches.update', $branch), 'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($this->data($request));

        return redirect()->route('branches.index')->with('flash', ['type' => 'success', 'message' => 'Branch updated.']);
    }

    public function destroy(Branch $branch)
    {
        try {
            $branch->delete();
        } catch (\Throwable $e) {
            // A branch with employees/appointments can't be deleted (ON DELETE RESTRICT).
            return redirect()->route('branches.index')->with('flash', ['type' => 'error', 'message' => 'Cannot delete: this branch is still in use by staff or appointments.']);
        }

        return redirect()->route('branches.index')->with('flash', ['type' => 'success', 'message' => 'Branch deleted.']);
    }

    private function data(Request $request): array
    {
        return [
            'name' => $request->input('name'),
            'type' => $request->input('type') ?: 'clinic',
            'phone' => $request->input('phone') ?: null,
            'address' => $request->input('address') ?: null,
        ];
    }
}
