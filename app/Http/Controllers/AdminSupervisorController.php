<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksUniqueEmail;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSupervisorController extends Controller
{
    use ChecksUniqueEmail;

    private const PER_PAGE = [10, 25, 50, 100];

    // ------------------------------------------------------------------
    // List
    // ------------------------------------------------------------------

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page');
        $perPage = in_array($perPage, self::PER_PAGE, true) ? $perPage : 10;

        $query = $this->filteredQuery($request)->withCount('students');

        match ($request->query('sort')) {
            'students' => $query->orderByDesc('students_count')->orderBy('name'),
            'company' => $query->orderBy('company_name')->orderBy('name'),
            'newest' => $query->orderByDesc('supervisor_id'),
            default => $query->orderBy('name'),
        };

        $supervisors = $query->paginate($perPage)->withQueryString();

        // Entries each supervisor still has to review (only the rows on this page are shown).
        $waiting = $this->waitingMap();
        $supervisors->getCollection()->each(function ($supervisor) use ($waiting) {
            $supervisor->setAttribute('waiting_count', (int) ($waiting[$supervisor->supervisor_id] ?? 0));
        });

        $chips = [
            ['key' => 'all', 'label' => 'All', 'count' => Supervisor::count(), 'query' => [], 'tone' => 'slate'],
            ['key' => 'waiting', 'label' => 'Reviews waiting', 'count' => $waiting->count(), 'query' => ['state' => 'waiting'], 'tone' => 'amber'],
            ['key' => 'no_students', 'label' => 'No students', 'count' => Supervisor::doesntHave('students')->count(), 'query' => ['state' => 'no_students'], 'tone' => 'sky'],
            ['key' => 'temp_password', 'label' => 'Temporary password', 'count' => Supervisor::where('must_change_password', true)->count(), 'query' => ['state' => 'temp_password'], 'tone' => 'rose'],
        ];

        $filters = collect($request->only(['q', 'company', 'state']))->filter(fn ($v) => $v !== null && $v !== '');
        $activeChip = 'custom';

        if ($filters->isEmpty()) {
            $activeChip = 'all';
        } elseif ($filters->count() === 1 && $request->query('state')) {
            $activeChip = $request->query('state');
        }

        $companies = Supervisor::whereNotNull('company_name')->where('company_name', '!=', '')->distinct()->orderBy('company_name')->pluck('company_name');
        $allSupervisors = Supervisor::orderBy('name')->get(['supervisor_id', 'name', 'company_name']);

        return view('admin.supervisors.index', compact('supervisors', 'chips', 'activeChip', 'companies', 'allSupervisors', 'perPage'));
    }

    // ------------------------------------------------------------------
    // Bulk actions
    // ------------------------------------------------------------------

    public function bulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:set_company,transfer_students,force_password_change,delete',
        ]);

        $ids = $request->boolean('apply_to_all')
            ? $this->filteredQuery($request)->pluck('supervisor_id')
            : collect($request->input('ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No supervisors were selected.');
        }

        $count = $ids->count();
        $noun = $count === 1 ? 'supervisor' : 'supervisors';

        switch ($request->input('action')) {
            case 'set_company':
                $data = $request->validate(['company_name' => 'required|string|max:255']);
                Supervisor::whereIn('supervisor_id', $ids)->update(['company_name' => trim($data['company_name'])]);

                return back()->with('success', 'Set the company of '.$count.' '.$noun.' to "'.trim($data['company_name']).'".');

            case 'transfer_students':
                $data = $request->validate(['to_supervisor_id' => 'required|integer|exists:supervisors,supervisor_id']);

                if ($ids->contains((int) $data['to_supervisor_id'])) {
                    return back()->with('error', 'Choose a supervisor who is not one of the selected supervisors.');
                }

                $moved = Student::whereIn('supervisor_id', $ids)->update(['supervisor_id' => $data['to_supervisor_id']]);

                return back()->with('success', 'Moved '.$moved.' '.str('student')->plural($moved).' to '.optional(Supervisor::find($data['to_supervisor_id']))->name.'.');

            case 'force_password_change':
                Supervisor::whereIn('supervisor_id', $ids)->update(['must_change_password' => true]);

                return back()->with('success', $count.' '.$noun.' will be asked to change their password at next login.');

            case 'delete':
                $unassigned = Student::whereIn('supervisor_id', $ids)->update(['supervisor_id' => null]);
                Supervisor::whereIn('supervisor_id', $ids)->get()->each->delete();

                return back()->with('success', 'Deleted '.$count.' '.$noun.'.'.($unassigned > 0 ? ' '.$unassigned.' '.str('student')->plural($unassigned).' are now unassigned.' : ''));
        }

        return back();
    }

    public function resetPassword($id)
    {
        $supervisor = Supervisor::findOrFail($id);
        $temporary = Str::random(10);

        $supervisor->password = Hash::make($temporary);
        $supervisor->must_change_password = true;
        $supervisor->save();

        return back()->with([
            'success' => 'Password reset for '.$supervisor->name.'. They must change it at next login.',
            'temp_password' => $temporary,
            'temp_for' => $supervisor->name,
        ]);
    }

    // ------------------------------------------------------------------
    // Create / edit / delete
    // ------------------------------------------------------------------

    public function create()
    {
        return view('admin.supervisors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule()],
            'password' => 'required|string|min:8',
            'company_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        Supervisor::create([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
            'company_name' => trim($data['company_name']),
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
            'must_change_password' => true,
        ]);

        return redirect('/admin/supervisors')->with('success', 'Company supervisor created.');
    }

    public function edit($id)
    {
        $supervisor = Supervisor::findOrFail($id);

        return view('admin.supervisors.edit', compact('supervisor'));
    }

    public function update(Request $request, $id)
    {
        $supervisor = Supervisor::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule('supervisors', 'supervisor_id', $supervisor->supervisor_id)],
            'password' => 'nullable|string|min:8',
            'company_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $supervisor->fill([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'company_name' => trim($data['company_name']),
            'position' => $data['position'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $supervisor->password = Hash::make($data['password']);
            $supervisor->must_change_password = true;
        }

        $supervisor->save();

        return redirect('/admin/supervisors')->with('success', 'Company supervisor updated.');
    }

    public function destroy($id)
    {
        $supervisor = Supervisor::findOrFail($id);

        Student::where('supervisor_id', $supervisor->supervisor_id)->update(['supervisor_id' => null]);
        $supervisor->delete();

        return redirect('/admin/supervisors')->with('success', 'Company supervisor removed. Their students are now unassigned.');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function filteredQuery(Request $request)
    {
        $query = Supervisor::query();

        $term = trim((string) $request->query('q'));
        if ($term !== '') {
            $like = '%'.mb_strtolower($term).'%';
            $query->where(function ($where) use ($like) {
                $where->whereRaw('lower(name) like ?', [$like])
                    ->orWhereRaw('lower(email) like ?', [$like])
                    ->orWhereRaw('lower(company_name) like ?', [$like])
                    ->orWhereRaw('lower(position) like ?', [$like]);
            });
        }

        if ($company = $request->query('company')) {
            $query->where('company_name', $company);
        }

        switch ($request->query('state')) {
            case 'no_students':
                $query->doesntHave('students');
                break;
            case 'temp_password':
                $query->where('must_change_password', true);
                break;
            case 'waiting':
                $query->whereIn('supervisor_id', $this->waitingMap()->keys());
                break;
        }

        return $query;
    }

    /** supervisor_id => number of entries still waiting for that supervisor's review. */
    protected function waitingMap()
    {
        return DB::table('logbooks')
            ->join('students', 'students.student_id', '=', 'logbooks.student_id')
            ->whereNotNull('students.supervisor_id')
            ->where(function ($where) {
                $where->whereNull('logbooks.supervisor_status')->orWhere('logbooks.supervisor_status', 'Pending');
            })
            ->select('students.supervisor_id', DB::raw('COUNT(*) as waiting'))
            ->groupBy('students.supervisor_id')
            ->pluck('waiting', 'supervisor_id');
    }
}
