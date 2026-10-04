<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class AdminSupervisorController extends Controller
{
    public function index()
    {
        $supervisors = Supervisor::withCount('students')->orderBy('name')->paginate(10);

        return view('admin.supervisors.index', compact('supervisors'));
    }

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
            'email' => ['required', 'email', 'max:255', $this->uniqueEmailRule($supervisor->supervisor_id)],
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
        Supervisor::findOrFail($id)->delete();

        return redirect('/admin/supervisors')->with('success', 'Company supervisor removed. Their students are now unassigned.');
    }

    /**
     * Login checks student, lecturer, admin and supervisor in turn and stops at the first match,
     * so the same email must never exist in two of these tables.
     */
    protected function uniqueEmailRule($ignoreSupervisorId = null)
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($ignoreSupervisorId) {
            $email = strtolower(trim((string) $value));

            foreach (['students', 'lecturers', 'admins', 'supervisors'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $query = DB::table($table)->whereRaw('lower(email) = ?', [$email]);

                if ($table === 'supervisors' && $ignoreSupervisorId) {
                    $query->where('supervisor_id', '!=', $ignoreSupervisorId);
                }

                if ($query->exists()) {
                    $fail('This email is already used by another account.');

                    return;
                }
            }
        };
    }
}
