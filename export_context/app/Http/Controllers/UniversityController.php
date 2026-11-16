<?php

namespace App\Http\Controllers;

use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UniversityController extends Controller
{
    public function index()
    {
        $universities = University::where('is_approved', true)
            ->orderBy('name')
            ->get();

        return view('student.university', compact('universities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'university_id' => 'nullable|exists:universities,id',
            'name' => 'required_without:university_id|nullable|string|max:255',
            'short_name' => 'nullable|string|max:50',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Student selects an existing university
        if (!empty($validated['university_id'])) {

            $university = University::where('is_approved', true)
                ->findOrFail($validated['university_id']);

        } else {

            // Prevent duplicate university names
            $existing = University::whereRaw(
                'LOWER(name) = LOWER(?)',
                [$validated['name']]
            )->first();

            if ($existing) {
                return back()->withErrors([
                    'name' => $existing->is_approved
                        ? 'This university already exists. Please select it.'
                        : 'This university is awaiting approval.'
                ])->withInput();
            }

            $logoUrl = null;
            $publicId = null;

            if ($request->hasFile('logo')) {
                $upload = Cloudinary::upload(
                    $request->file('logo')->getRealPath(),
                    [
                        'folder' => 'elogbook/universities',
                        'resource_type' => 'image',
                    ]
                );

                $logoUrl = $upload->getSecurePath();
                $publicId = $upload->getPublicId();
            }

            $university = University::create([
                'name' => $validated['name'],
                'short_name' => $validated['short_name'] ?? null,
                'logo_url' => $logoUrl,
                'logo_public_id' => $publicId,
                'is_approved' => false,
            ]);

            return back()->with(
                'success',
                'University submitted for admin approval.'
            );
        }

        // Assign selected university to student
        Auth::user()->update([
            'university_id' => $university->id,
        ]);

        return redirect()->back()->with(
            'success',
            'University information updated successfully!'
        );
    }
}