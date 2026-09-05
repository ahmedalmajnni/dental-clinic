<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Employee;
use App\Models\Specialty;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    private const JOBS = ['admin', 'doctor', 'reception', 'lab_tech'];

    public function index()
    {
        return view('employees.index', ['employees' => Employee::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('employees.form', [
            'employee' => new Employee(),
            'specialties' => Specialty::orderBy('name')->get(),
            'jobTitles' => self::JOBS, 'action' => route('employees.store'), 'method' => 'POST',
        ]);
    }

    public function store(Request $request)
    {
        Employee::create($this->data($request));

        return redirect()->route('employees.index')->with('flash', ['type' => 'success', 'message' => 'Employee created.']);
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', [
            'employee' => $employee,
            'specialties' => Specialty::orderBy('name')->get(),
            'jobTitles' => self::JOBS, 'action' => route('employees.update', $employee), 'method' => 'PUT',
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->data($request, $employee));

        return redirect()->route('employees.index')->with('flash', ['type' => 'success', 'message' => 'Employee updated.']);
    }

    public function destroy(Employee $employee)
    {
        // Guard: never delete the last admin — their login is cascade-deleted
        // with them, which would lock everyone out of the admin area.
        $employee->loadMissing('account');
        if ($employee->account && $employee->account->role === 'admin'
            && Account::where('role', 'admin')->count() <= 1) {
            return redirect()->route('employees.index')->with('flash', ['type' => 'error', 'message' => 'Cannot delete the last admin. Create another admin first.']);
        }

        try {
            $employee->delete();
        } catch (\Throwable $e) {
            return redirect()->route('employees.index')->with('flash', ['type' => 'error', 'message' => 'Cannot delete: this employee is linked to appointments or reports.']);
        }

        return redirect()->route('employees.index')->with('flash', ['type' => 'success', 'message' => 'Employee deleted.']);
    }

    private function data(Request $request, ?Employee $employee = null): array
    {
        return [
            'name' => $request->input('name'),
            'job_title' => $request->input('job_title'),
            'specialty' => $request->input('specialty') ?: null,
            'phone' => $request->input('phone') ?: null,
        ];
    }
}
