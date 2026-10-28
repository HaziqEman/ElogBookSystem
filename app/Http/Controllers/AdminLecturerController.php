<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksUniqueEmail;
use App\Models\Lecturer;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminLecturerController extends Controller
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
            'newest' => $query->orderByDesc('lecturer_id'),
            default => $query->orderBy('name'),
        };

        $lecturers = $query->paginate($perPage)->withQueryString();

        $chips = [
            ['key' => 'all', 'label' => 'All', 'count' => Lecturer::count(), 'query' => [], 'tone' => 'slate'],
            ['key' => 'has_students', 'label' => 'With students', 'count' => Lecturer::has('students')->count(), 'query' => ['state' => 'has_students'], 'tone' => 'emerald'],
            ['key' => 'no_students', 'label' => 'No students', 'count' => Lecturer::doesntHave('students')->count(), 'query' => ['state' => 'no_students'], 'tone' => 'amber'],
        ];

        $filters = collect($request->only(['q', 'faculty', 'state']))->filter(fn ($v) => $v !== null && $v !== '');
        $activeChip = 'custom';

        if ($filters->isEmpty()) {
            $activeChip = 'all';
        } elseif ($filters->count() === 1 && $request->query('state')) {
            $activeChip = $request->query('state');
        }

        $faculties = Lecturer::whereNotNull('faculty')->where('faculty', '!=', '')->distinct()->orderBy('faculty')->pluck('faculty');
        $allLecturers = Lecturer::orderBy('name')->get(['lecturer_id', 'name']);

        return view('admin.lecturers.index', compact('lecturers', 'chips', 'activeChip', 'faculties', 'allLecturers', 'perPage'));
    }

    // ------------------------------------------------------------------
    // Bulk actions
    // ------------------------------------------------------------------

    public function bulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:set_faculty,transfer_students,delete',
        ]);

        $ids = $request->boolean('apply_to_all')
            ? $this->filteredQuery($request)->pluck('lecturer_id')
            : collect($request->input('ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No lecturers were selected.');
        }

        $count = $ids->count();
        $noun = $count === 1 ? 'lecturer' : 'lecturers';

        switch ($request->input('action')) {
            case 'set_faculty':
                $data = $request->validate(['faculty' => 'required|string|max:255']);
                Lecturer::whereIn('lecturer_id', $ids)->update(['faculty' => trim($data['faculty'])]);

                return back()->with('success', 'Set the faculty of '.$count.' '.$noun.' to "'.trim($data['faculty']).'".');

            case 'transfer_students':
                $data = $request->validate(['to_lecturer_id' => 'required|integer|exists:lecturers,lecturer_id']);

                if ($ids->contains((int) $data['to_lecturer_id'])) {
                    return back()->with('error', 'Choose a lecturer who is not one of the selected lecturers.');
                }

                $moved = Student::whereIn('lecturer_id', $ids)->update(['lecturer_id' => $data['to_lecturer_id']]);

                return back()->with('success', 'Moved '.$moved.' '.str('student')->plural($moved).' to '.optional(Lecturer::find($data['to_lecturer_id']))->name.'.');

            case 'delete':
                $withStudents = Student::whereIn('lecturer_id', $ids)->distinct()->pluck('lecturer_id');
                $deletable = $ids->diff($withStudents);

                Lecturer::whereIn('lecturer_id', $deletable)->get()->each->delete();

                $skipped = $withStudents->count();
                $message = 'Deleted '.$deletable->count().' '.str('lecturer')->plural($deletable->count()).'.';

                if ($skipped > 0) {
                    $message .= ' '.$skipped.' kept because they still have students. Move those students to another lecturer first.';
                }

                return back()->with($skipped > 0 && $deletable->isEmpty() ? 'error' : 'success', $message);
        }

        return back();
    }

    public function resetPassword($id)
    {
        $lecturer = Lecturer::findOrFail($id);
        $temporary = Str::random(10);

        $lecturer->forceFill(['password' => Hash::make($temporary)])->save();

        return back()->with([
            'success' => 'Password reset for '.$lecturer->name.'.',
            'temp_password' => $temporary,
            'temp_for' => $lecturer->name,
        ]);
    }

    // ------------------------------------------------------------------
    // Create / edit / delete
    // ------------------------------------------------------------------

    public function create()
    {
        return view('admin.lecturers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule()],
            'password' => 'required|string|min:6',
            'faculty' => 'nullable|string|max:255',
        ]);

        Lecturer::create([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'password' => Hash::make($data['password']),
            'faculty' => $data['faculty'] ?? null,
        ]);

        return redirect('/admin/lecturers')->with('success', 'Lecturer created.');
    }

    public function edit($id)
    {
        $lecturer = Lecturer::findOrFail($id);

        return view('admin.lecturers.edit', compact('lecturer'));
    }

    public function update(Request $request, $id)
    {
        $lecturer = Lecturer::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule('lecturers', 'lecturer_id', $lecturer->lecturer_id)],
            'password' => 'nullable|string|min:6',
            'faculty' => 'nullable|string|max:255',
        ]);

        $lecturer->fill([
            'name' => trim($data['name']),
            'email' => trim($data['email']),
            'faculty' => $data['faculty'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $lecturer->password = Hash::make($data['password']);
        }

        $lecturer->save();

        return redirect('/admin/lecturers')->with('success', 'Lecturer updated.');
    }

    public function destroy($id)
    {
        $lecturer = Lecturer::findOrFail($id);

        // Students need a lecturer, so never delete one who still has students.
        $students = Student::where('lecturer_id', $lecturer->lecturer_id)->count();

        if ($students > 0) {
            return back()->with('error', $lecturer->name.' still has '.$students.' '.str('student')->plural($students).'. Move them to another lecturer first (select the lecturer, then use "Move students").');
        }

        $lecturer->delete();

        return back()->with('success', 'Lecturer deleted.');
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function filteredQuery(Request $request)
    {
        $query = Lecturer::query();

        $term = trim((string) $request->query('q'));
        if ($term !== '') {
            $like = '%'.mb_strtolower($term).'%';
            $query->where(function ($where) use ($like) {
                $where->whereRaw('lower(name) like ?', [$like])
                    ->orWhereRaw('lower(email) like ?', [$like])
                    ->orWhereRaw('lower(faculty) like ?', [$like]);
            });
        }

        if ($faculty = $request->query('faculty')) {
            $query->where('faculty', $faculty);
        }

        match ($request->query('state')) {
            'has_students' => $query->has('students'),
            'no_students' => $query->doesntHave('students'),
            default => null,
        };

        return $query;
    }
}
