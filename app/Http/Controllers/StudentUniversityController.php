<?php

namespace App\Http\Controllers;

use App\Models\University;
use App\Services\CloudinaryUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentUniversityController extends Controller
{
    /**
     * Saves the "University Information" card: the student either picks an existing
     * university or adds a new one (name + logo), plus the internship organization
     * address and department.
     */
    public function update(Request $request, CloudinaryUploader $cloud)
    {
        $student = Auth::guard('student')->user();

        $data = $request->validate([
            'mode' => 'required|in:existing,new',
            'export' => 'nullable|boolean',
            'organization_address' => 'nullable|string|max:500',
            'department' => 'nullable|string|max:255',
            'university_id' => 'nullable|integer',
            'name' => 'required_if:mode,new|nullable|string|min:2|max:255',
            // `image` already rejects SVG, which can carry scripts.
            'logo' => 'required_if:mode,new|nullable|image|mimes:png,jpg,jpeg,webp|max:2048|dimensions:max_width=4000,max_height=4000',
        ], [
            'logo.required_if' => 'Please upload the university logo.',
            'logo.image' => 'The logo must be a PNG, JPG or WebP image.',
            'logo.mimes' => 'The logo must be a PNG, JPG or WebP image.',
            'logo.max' => 'The logo must be 2 MB or smaller.',
            'logo.dimensions' => 'The logo image is too large (max 4000 x 4000 px).',
            'name.required_if' => 'Please enter the university name.',
        ]);

        $universityId = $student->university_id;
        $notice = 'University information saved.';

        if ($data['mode'] === 'existing') {
            // Blank means "leave the university as it is" so the student can still save just the address.
            if (filled($data['university_id'] ?? null)) {
                $university = University::visibleTo($student->student_id)
                    ->whereKey((int) $data['university_id'])
                    ->first();

                if (! $university) {
                    return back()->withInput()->withErrors(['university_id' => 'Please choose a university from the list.']);
                }

                $universityId = $university->university_id;
            }

            if (! $universityId && $request->boolean('export')) {
                return back()->withInput()->withErrors(['university_id' => 'Please choose or add your university before exporting.']);
            }
        } else {
            $name = University::normalizeName($data['name']);
            $existing = University::findByName($name);

            if ($existing) {
                // Already there (maybe under different capitalisation): link to it instead of duplicating.
                $universityId = $existing->university_id;
                $notice = 'That university already exists, so you were linked to it.';
            } else {
                try {
                    $logo = $cloud->upload($request->file('logo'));
                } catch (\Throwable $e) {
                    report($e);

                    return back()->withInput()->withErrors(['logo' => 'The logo could not be uploaded. Please try again.']);
                }

                try {
                    $university = University::create([
                        'name' => $name,
                        'logo_url' => $logo['url'],
                        'logo_public_id' => $logo['public_id'],
                        'is_verified' => false,
                        'created_by_student_id' => $student->student_id,
                    ]);
                } catch (\Throwable $e) {
                    // Two students adding the same name at once: drop the orphaned upload.
                    $cloud->destroy($logo['public_id']);
                    report($e);

                    return back()->withInput()->withErrors(['name' => 'Could not save the university. Please try again.']);
                }

                $universityId = $university->university_id;
                $notice = 'University added. It will show for everyone once an admin approves it.';
            }
        }

        $student->forceFill([
            'university_id' => $universityId,
            'organization_address' => $data['organization_address'] ?? null,
            'department' => $data['department'] ?? null,
        ])->save();

        // Opened from "Export PDF": carry on to the PDF once the details are saved.
        if ($request->boolean('export')) {
            return redirect()->route('student.logbooks.pdf');
        }

        return redirect('/student/dashboard')->with('success', $notice);
    }

    /** "Did you mean...?" suggestions while the student types a new university name. */
    public function search(Request $request)
    {
        $term = mb_strtolower(University::normalizeName((string) $request->query('q', '')));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $student = Auth::guard('student')->user();

        $matches = University::visibleTo($student->student_id)
            ->whereRaw('lower(name) like ?', ['%'.addcslashes($term, '%_\\').'%'])
            ->orderBy('name')
            ->limit(5)
            ->get(['university_id', 'name', 'logo_url']);

        return response()->json($matches);
    }
}
