<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\Treatment;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * The clinic's public front page. Anyone already signed in is sent straight
     * to their own dashboard, so this is only ever rendered for visitors.
     *
     * The figures and lists are real data, but the front door must never show a
     * 500 to a visitor, so every lookup falls back to an empty value if the
     * database is unreachable.
     */
    public function index()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $branches = rescue(fn () => Branch::where('type', 'clinic')->orderBy('name')->get(), collect(), false);

        $doctors = rescue(fn () => Employee::with('branch')
            ->where('job_title', 'doctor')
            ->orderBy('name')
            ->get(), collect(), false);

        $stats = rescue(fn () => [
            'patients' => Patient::count(),
            'doctors' => Employee::where('job_title', 'doctor')->count(),
            'branches' => Branch::where('type', 'clinic')->count(),
            'treatments' => Treatment::where('status', 'done')->count(),
        ], ['patients' => 0, 'doctors' => 0, 'branches' => 0, 'treatments' => 0], false);

        // The "what we do" list is drawn from the procedures actually performed
        // here, so it always describes this clinic rather than a generic menu.
        $services = rescue(fn () => Treatment::query()
            ->selectRaw('procedure, count(*) as n')
            ->groupBy('procedure')
            ->orderByDesc('n')
            ->limit(8)
            ->pluck('procedure'), collect(), false);

        return view('home.landing', compact('branches', 'doctors', 'stats', 'services'));
    }
}
