<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\University;
use App\Services\CloudinaryUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUniversityController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = trim((string) $request->query('q', ''));

        $query = University::withCount('students')->with('creator:student_id,name');

        if ($status === 'pending') {
            $query->where('is_verified', false);
        } elseif ($status === 'verified') {
            $query->where('is_verified', true);
        }

        if ($search !== '') {
            $query->whereRaw('lower(name) like ?', ['%'.addcslashes(mb_strtolower($search), '%_\\').'%']);
        }

        // Pending ones first so they are not missed.
        $universities = $query->orderBy('is_verified')->orderBy('name')->paginate(15)->withQueryString();

        $pendingCount = University::where('is_verified', false)->count();
        $allUniversities = University::orderBy('name')->get(['university_id', 'name']);

        return view('admin.universities.index', compact('universities', 'pendingCount', 'allUniversities', 'status', 'search'));
    }

    public function create()
    {
        return view('admin.universities.create');
    }

    public function store(Request $request, CloudinaryUploader $cloud)
    {
        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=4000,max_height=4000',
        ]);

        $name = University::normalizeName($data['name']);

        if (University::findByName($name)) {
            return back()->withInput()->withErrors(['name' => 'A university with this name already exists.']);
        }

        $logo = null;

        if ($request->hasFile('logo')) {
            try {
                $logo = $cloud->upload($request->file('logo'));
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors(['logo' => 'The logo could not be uploaded. Please try again.']);
            }
        }

        University::create([
            'name' => $name,
            'logo_url' => $logo['url'] ?? null,
            'logo_public_id' => $logo['public_id'] ?? null,
            'is_verified' => true,
        ]);

        return redirect('/admin/universities')->with('success', 'University added.');
    }

    public function edit($id)
    {
        $university = University::withCount('students')->findOrFail($id);

        return view('admin.universities.edit', compact('university'));
    }

    public function update(Request $request, $id, CloudinaryUploader $cloud)
    {
        $university = University::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=4000,max_height=4000',
            'is_verified' => 'nullable|boolean',
        ]);

        $name = University::normalizeName($data['name']);

        if (University::findByName($name, $university->university_id)) {
            return back()->withInput()->withErrors(['name' => 'Another university already uses this name. Use "Merge" on the list instead.']);
        }

        $payload = [
            'name' => $name,
            'is_verified' => $request->boolean('is_verified'),
        ];

        $oldPublicId = null;

        if ($request->hasFile('logo')) {
            try {
                $logo = $cloud->upload($request->file('logo'));
            } catch (\Throwable $e) {
                report($e);

                return back()->withInput()->withErrors(['logo' => 'The logo could not be uploaded. Please try again.']);
            }

            $oldPublicId = $university->logo_public_id;
            $payload['logo_url'] = $logo['url'];
            $payload['logo_public_id'] = $logo['public_id'];
        }

        $university->update($payload);

        // Remove the replaced image only after the new one is safely saved.
        $cloud->destroy($oldPublicId);

        return redirect('/admin/universities')->with('success', 'University updated.');
    }

    public function destroy($id, CloudinaryUploader $cloud)
    {
        $university = University::withCount('students')->findOrFail($id);
        $count = $university->students_count;
        $publicId = $university->logo_public_id;

        // students.university_id is set to null by the foreign key, students themselves stay.
        $university->delete();
        $cloud->destroy($publicId);

        return redirect('/admin/universities')->with('success', 'University deleted.'.($count ? " {$count} ".str('student')->plural($count).' now have no university set.' : ''));
    }

    public function verify($id)
    {
        University::findOrFail($id)->update(['is_verified' => true]);

        return back()->with('success', 'University approved. All students can now choose it.');
    }

    /** Moves every student to another university, then deletes this duplicate. */
    public function merge(Request $request, $id, CloudinaryUploader $cloud)
    {
        $source = University::findOrFail($id);

        $data = $request->validate([
            'target_id' => 'required|integer|exists:universities,university_id',
        ], [
            'target_id.required' => 'Choose the university to merge into.',
        ]);

        if ((int) $data['target_id'] === (int) $source->university_id) {
            return back()->with('error', 'Choose a different university to merge into.');
        }

        $target = University::findOrFail($data['target_id']);
        $logoMoved = false;

        DB::transaction(function () use ($source, $target, &$logoMoved) {
            Student::where('university_id', $source->university_id)->update(['university_id' => $target->university_id]);

            // Keep the better data: reuse the duplicate's logo if the target has none.
            if (! $target->logo_url && $source->logo_url) {
                $target->update(['logo_url' => $source->logo_url, 'logo_public_id' => $source->logo_public_id]);
                $logoMoved = true;
            }

            $source->delete();
        });

        if (! $logoMoved) {
            $cloud->destroy($source->logo_public_id);
        }

        return back()->with('success', "Merged \"{$source->name}\" into \"{$target->name}\".");
    }
}
