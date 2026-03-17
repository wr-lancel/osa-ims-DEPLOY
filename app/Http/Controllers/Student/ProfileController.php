<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentEducationalBackground;
use App\Models\StudentEmergencyContact;
use App\Models\StudentFamilyInfo;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the student profile page with all profile data.
     */
    public function show(): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        if (!$studentNumber) {
            return Inertia::render('Student/Profile', [
                'student' => null,
                'profile' => null,
                'educationalBackground' => null,
                'familyInfo' => null,
                'emergencyContact' => null,
                'enrollmentHistory' => [],
            ]);
        }

        $student = Student::with([
            'enrollments.academicCalendar',
            'enrollments.course',
            'enrollments.section',
            'profile',
            'educationalBackground',
            'familyInfo',
            'emergencyContact',
        ])->where('student_number', $studentNumber)->first();

        if (!$student) {
            return Inertia::render('Student/Profile', [
                'student' => null,
                'profile' => null,
                'educationalBackground' => null,
                'familyInfo' => null,
                'emergencyContact' => null,
                'enrollmentHistory' => [],
            ]);
        }

        // Transform enrollments for display
        $enrollmentHistory = $student->enrollments->map(function ($enrollment) {
            return [
                'enrollment_id' => $enrollment->enrollment_id,
                'term_label' => $enrollment->academicCalendar?->display_label ?? 'Unknown Term',
                'course_name' => $enrollment->course?->course_name ?? 'N/A',
                'course_code' => $enrollment->course?->course_code ?? 'N/A',
                'section_name' => $enrollment->section?->section_name ?? 'N/A',
                'year_level' => $enrollment->year_level ?? 'N/A',
            ];
        })->sortByDesc('enrollment_id')->values();

        $profile = $student->profile;
        $educationalBackground = $student->educationalBackground;
        $familyInfo = $student->familyInfo;
        $emergencyContact = $student->emergencyContact;

        return Inertia::render('Student/Profile', [
            'student' => [
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'middle_name' => $student->middle_name,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'birth_date' => $student->birth_date?->format('Y-m-d'),
                'address' => $student->address,
                'status' => $student->status,
            ],
            'profile' => $profile ? [
                'birth_place' => $profile->birth_place,
                'gender' => $profile->gender,
                'citizenship' => $profile->citizenship,
                'civil_status' => $profile->civil_status,
                'spouse_name' => $profile->spouse_name,
                'is_single_parent' => $profile->is_single_parent,
                'has_disability' => $profile->has_disability,
                'disability_details' => $profile->disability_details,
                'is_employed' => $profile->is_employed,
                'company_name' => $profile->company_name,
            ] : null,
            'educationalBackground' => $educationalBackground ? [
                'elementary_school' => $educationalBackground->elementary_school,
                'elementary_address' => $educationalBackground->elementary_address,
                'elementary_graduated' => $educationalBackground->elementary_graduated?->format('Y-m'),
                'senior_high_school' => $educationalBackground->senior_high_school,
                'senior_high_strand' => $educationalBackground->senior_high_strand,
                'senior_high_address' => $educationalBackground->senior_high_address,
                'senior_high_graduated' => $educationalBackground->senior_high_graduated?->format('Y-m'),
                'honors_received' => $educationalBackground->honors_received,
            ] : null,
            'familyInfo' => $familyInfo ? [
                'father_last_name' => $familyInfo->father_last_name,
                'father_first_name' => $familyInfo->father_first_name,
                'father_middle_initial' => $familyInfo->father_middle_initial,
                'father_occupation' => $familyInfo->father_occupation,
                'mother_maiden_last_name' => $familyInfo->mother_maiden_last_name,
                'mother_first_name' => $familyInfo->mother_first_name,
                'mother_middle_initial' => $familyInfo->mother_middle_initial,
                'mother_occupation' => $familyInfo->mother_occupation,
            ] : null,
            'emergencyContact' => $emergencyContact ? [
                'contact_name' => $emergencyContact->contact_name,
                'relationship' => $emergencyContact->relationship,
                'contact_number' => $emergencyContact->contact_number,
                'contact_address' => $emergencyContact->contact_address,
            ] : null,
            'enrollmentHistory' => $enrollmentHistory,
        ]);
    }

    /**
     * Update the student profile data across all sections.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        if (!$studentNumber) {
            return redirect()->back()->with('error', 'Unable to identify your student account.');
        }

        $student = Student::where('student_number', $studentNumber)->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        // Validate all sections
        $validated = $request->validate([
            // Student basic info (editable fields only)
            'phone'      => 'required|string|max:20',
            'email'      => 'required|email|max:255',
            'address'    => 'required|string|max:500',
            'birth_date' => 'required|date',

            // Profile
            'birth_place'        => 'required|string|max:255',
            'gender'             => 'required|in:male,female,other',
            'citizenship'        => 'required|string|max:255',
            'civil_status'       => 'required|in:single,married,widowed',
            'spouse_name'        => 'nullable|string|max:255',
            'is_single_parent'   => 'boolean',
            'has_disability'     => 'boolean',
            'disability_details' => 'nullable|string|max:255',
            'is_employed'        => 'boolean',
            'company_name'       => 'nullable|string|max:255',

            // Educational Background
            'elementary_school'     => 'required|string|max:255',
            'elementary_address'    => 'required|string|max:255',
            'elementary_graduated'  => 'required|date_format:Y-m',
            'senior_high_school'    => 'required|string|max:255',
            'senior_high_strand'    => 'required|string|max:255',
            'senior_high_address'   => 'required|string|max:255',
            'senior_high_graduated' => 'required|date_format:Y-m',
            'honors_received'       => 'nullable|string|max:1000',

            // Family Info
            'father_last_name'        => 'required|string|max:255',
            'father_first_name'       => 'required|string|max:255',
            'father_middle_initial'   => 'nullable|string|max:10',
            'father_occupation'       => 'required|string|max:255',
            'mother_maiden_last_name' => 'required|string|max:255',
            'mother_first_name'       => 'required|string|max:255',
            'mother_middle_initial'   => 'nullable|string|max:10',
            'mother_occupation'       => 'required|string|max:255',

            // Emergency Contact
            'contact_name'    => 'required|string|max:255',
            'relationship'    => 'required|string|max:255',
            'contact_number'  => 'required|string|max:20',
            'contact_address' => 'required|string|max:500',
        ]);

        try {
            // Update student basic info (only editable fields)
            $student->update([
                'phone' => $validated['phone'] ?? $student->phone,
                'email' => $validated['email'] ?? $student->email,
                'address' => $validated['address'] ?? $student->address,
                'birth_date' => $validated['birth_date'] ?? $student->birth_date,
            ]);

            // Update or create profile
            StudentProfile::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'birth_place' => $validated['birth_place'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'citizenship' => $validated['citizenship'] ?? null,
                    'civil_status' => $validated['civil_status'] ?? 'single',
                    'spouse_name' => $validated['spouse_name'] ?? null,
                    'is_single_parent' => $validated['is_single_parent'] ?? false,
                    'has_disability' => $validated['has_disability'] ?? false,
                    'disability_details' => $validated['disability_details'] ?? null,
                    'is_employed' => $validated['is_employed'] ?? false,
                    'company_name' => $validated['company_name'] ?? null,
                ]
            );

            // Update or create educational background
            StudentEducationalBackground::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'elementary_school' => $validated['elementary_school'] ?? null,
                    'elementary_address' => $validated['elementary_address'] ?? null,
                    'elementary_graduated' => isset($validated['elementary_graduated']) ? $validated['elementary_graduated'] . '-01' : null,
                    'senior_high_school' => $validated['senior_high_school'] ?? null,
                    'senior_high_strand' => $validated['senior_high_strand'] ?? null,
                    'senior_high_address' => $validated['senior_high_address'] ?? null,
                    'senior_high_graduated' => isset($validated['senior_high_graduated']) ? $validated['senior_high_graduated'] . '-01' : null,
                    'honors_received' => $validated['honors_received'] ?? null,
                ]
            );

            // Update or create family info
            StudentFamilyInfo::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'father_last_name' => $validated['father_last_name'] ?? null,
                    'father_first_name' => $validated['father_first_name'] ?? null,
                    'father_middle_initial' => $validated['father_middle_initial'] ?? null,
                    'father_occupation' => $validated['father_occupation'] ?? null,
                    'mother_maiden_last_name' => $validated['mother_maiden_last_name'] ?? null,
                    'mother_first_name' => $validated['mother_first_name'] ?? null,
                    'mother_middle_initial' => $validated['mother_middle_initial'] ?? null,
                    'mother_occupation' => $validated['mother_occupation'] ?? null,
                ]
            );

            // Update or create emergency contact
            StudentEmergencyContact::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'contact_name' => $validated['contact_name'] ?? null,
                    'relationship' => $validated['relationship'] ?? null,
                    'contact_number' => $validated['contact_number'] ?? null,
                    'contact_address' => $validated['contact_address'] ?? null,
                ]
            );

            Log::info("Student profile updated: {$studentNumber} by user {$user->user_id}");

            return redirect()->route('student.profile')
                ->with('success', 'Profile updated successfully.');

        } catch (\Exception $e) {
            Log::error("Failed to update student profile: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to update profile. Please try again.');
        }
    }
}
