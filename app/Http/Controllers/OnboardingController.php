<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentEducationalBackground;
use App\Models\StudentEmergencyContact;
use App\Models\StudentFamilyInfo;
use App\Models\StudentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the forced password change page.
     */
    public function showChangePassword(): Response|RedirectResponse
    {
        if (!Auth::user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Onboarding/ChangePassword');
    }

    /**
     * Handle the forced password change.
     */
    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => $request->password,
            'must_change_password' => false,
        ]);

        Log::info("User completed forced password change: user_id={$user->user_id}");

        // Students go to profile completion next; admins go to dashboard.
        if ($user->student_number) {
            $student = $user->student;
            if ($student && !$student->profile_completed) {
                return redirect()->route('onboarding.complete-profile');
            }
            return redirect()->route('student.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * Show the profile completion page (students only).
     */
    public function showCompleteProfile(): Response|RedirectResponse
    {
        $user = Auth::user();

        if (!$user->student_number) {
            return redirect()->route('admin.dashboard');
        }

        $student = Student::with([
            'enrollments.academicCalendar',
            'enrollments.course',
            'profile',
            'educationalBackground',
            'familyInfo',
            'emergencyContact',
        ])->where('student_number', $user->student_number)->first();

        if (!$student) {
            return redirect()->route('student.dashboard');
        }

        if ($student->profile_completed) {
            return redirect()->route('student.dashboard');
        }

        $enrollmentHistory = $student->enrollments->map(fn($e) => [
            'course_name' => $e->course?->course_name ?? 'N/A',
            'course_code' => $e->course?->course_code ?? 'N/A',
            'year_level' => $e->year_level ?? 'N/A',
        ])->sortByDesc('enrollment_id')->values();

        return Inertia::render('Onboarding/CompleteProfile', [
            'student' => [
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'middle_name' => $student->middle_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'birth_date' => $student->birth_date?->format('Y-m-d'),
                'address' => $student->address,
            ],
            'profile' => $student->profile ? [
                'birth_place' => $student->profile->birth_place,
                'gender' => $student->profile->gender,
                'citizenship' => $student->profile->citizenship,
                'civil_status' => $student->profile->civil_status,
                'spouse_name' => $student->profile->spouse_name,
                'is_single_parent' => $student->profile->is_single_parent,
                'has_disability' => $student->profile->has_disability,
                'disability_details' => $student->profile->disability_details,
                'is_employed' => $student->profile->is_employed,
                'company_name' => $student->profile->company_name,
            ] : null,
            'educationalBackground' => $student->educationalBackground ? [
                'elementary_school' => $student->educationalBackground->elementary_school,
                'elementary_address' => $student->educationalBackground->elementary_address,
                'elementary_graduated' => $student->educationalBackground->elementary_graduated?->format('Y-m'),
                'senior_high_school' => $student->educationalBackground->senior_high_school,
                'senior_high_strand' => $student->educationalBackground->senior_high_strand,
                'senior_high_address' => $student->educationalBackground->senior_high_address,
                'senior_high_graduated' => $student->educationalBackground->senior_high_graduated?->format('Y-m'),
                'honors_received' => $student->educationalBackground->honors_received,
            ] : null,
            'familyInfo' => $student->familyInfo ? [
                'father_last_name' => $student->familyInfo->father_last_name,
                'father_first_name' => $student->familyInfo->father_first_name,
                'father_middle_initial' => $student->familyInfo->father_middle_initial,
                'father_occupation' => $student->familyInfo->father_occupation,
                'mother_maiden_last_name' => $student->familyInfo->mother_maiden_last_name,
                'mother_first_name' => $student->familyInfo->mother_first_name,
                'mother_middle_initial' => $student->familyInfo->mother_middle_initial,
                'mother_occupation' => $student->familyInfo->mother_occupation,
            ] : null,
            'emergencyContact' => $student->emergencyContact ? [
                'contact_name' => $student->emergencyContact->contact_name,
                'relationship' => $student->emergencyContact->relationship,
                'contact_number' => $student->emergencyContact->contact_number,
                'contact_address' => $student->emergencyContact->contact_address,
            ] : null,
            'enrollmentHistory' => $enrollmentHistory,
        ]);
    }

    /**
     * Handle profile completion submission (students only).
     */
    public function completeProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        if (!$studentNumber) {
            return redirect()->route('admin.dashboard');
        }

        $student = Student::where('student_number', $studentNumber)->first();

        if (!$student) {
            return redirect()->back()->with('error', 'Student record not found.');
        }

        $validated = $request->validate([
            // Student basic info
            'phone'                  => 'required|string|max:20',
            'email'                  => 'required|email|max:255',
            'address'                => 'required|string|max:500',
            'birth_date'             => 'required|date',

            // Personal profile
            'birth_place'            => 'required|string|max:255',
            'gender'                 => 'required|in:male,female,other',
            'citizenship'            => 'required|string|max:255',
            'civil_status'           => 'required|in:single,married,widowed',
            'spouse_name'            => 'nullable|string|max:255',
            'is_single_parent'       => 'boolean',
            'has_disability'         => 'boolean',
            'disability_details'     => 'nullable|string|max:255',
            'is_employed'            => 'boolean',
            'company_name'           => 'nullable|string|max:255',

            // Educational background
            'elementary_school'      => 'required|string|max:255',
            'elementary_address'     => 'required|string|max:255',
            'elementary_graduated'   => 'required|date_format:Y-m',
            'senior_high_school'     => 'required|string|max:255',
            'senior_high_strand'     => 'required|string|max:255',
            'senior_high_address'    => 'required|string|max:255',
            'senior_high_graduated'  => 'required|date_format:Y-m',
            'honors_received'        => 'nullable|string|max:1000',

            // Family info
            'father_last_name'       => 'required|string|max:255',
            'father_first_name'      => 'required|string|max:255',
            'father_middle_initial'  => 'nullable|string|max:10',
            'father_occupation'      => 'required|string|max:255',
            'mother_maiden_last_name'=> 'required|string|max:255',
            'mother_first_name'      => 'required|string|max:255',
            'mother_middle_initial'  => 'nullable|string|max:10',
            'mother_occupation'      => 'required|string|max:255',

            // Emergency contact
            'contact_name'           => 'required|string|max:255',
            'relationship'           => 'required|string|max:255',
            'contact_number'         => 'required|string|max:20',
            'contact_address'        => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $student->update([
                'phone'      => $validated['phone'],
                'email'      => $validated['email'],
                'address'    => $validated['address'],
                'birth_date' => $validated['birth_date'],
                'profile_completed' => true,
            ]);

            StudentProfile::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'birth_place'        => $validated['birth_place'],
                    'gender'             => $validated['gender'],
                    'citizenship'        => $validated['citizenship'],
                    'civil_status'       => $validated['civil_status'],
                    'spouse_name'        => $validated['spouse_name'] ?? null,
                    'is_single_parent'   => $validated['is_single_parent'] ?? false,
                    'has_disability'     => $validated['has_disability'] ?? false,
                    'disability_details' => $validated['disability_details'] ?? null,
                    'is_employed'        => $validated['is_employed'] ?? false,
                    'company_name'       => $validated['company_name'] ?? null,
                ]
            );

            StudentEducationalBackground::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'elementary_school'     => $validated['elementary_school'],
                    'elementary_address'    => $validated['elementary_address'],
                    'elementary_graduated'  => $validated['elementary_graduated'] . '-01',
                    'senior_high_school'    => $validated['senior_high_school'],
                    'senior_high_strand'    => $validated['senior_high_strand'],
                    'senior_high_address'   => $validated['senior_high_address'],
                    'senior_high_graduated' => $validated['senior_high_graduated'] . '-01',
                    'honors_received'       => $validated['honors_received'] ?? null,
                ]
            );

            StudentFamilyInfo::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'father_last_name'        => $validated['father_last_name'],
                    'father_first_name'       => $validated['father_first_name'],
                    'father_middle_initial'   => $validated['father_middle_initial'] ?? null,
                    'father_occupation'       => $validated['father_occupation'],
                    'mother_maiden_last_name' => $validated['mother_maiden_last_name'],
                    'mother_first_name'       => $validated['mother_first_name'],
                    'mother_middle_initial'   => $validated['mother_middle_initial'] ?? null,
                    'mother_occupation'       => $validated['mother_occupation'],
                ]
            );

            StudentEmergencyContact::updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'contact_name'    => $validated['contact_name'],
                    'relationship'    => $validated['relationship'],
                    'contact_number'  => $validated['contact_number'],
                    'contact_address' => $validated['contact_address'],
                ]
            );

            DB::commit();

            Log::info("Student completed profile onboarding: student_number={$studentNumber}");

            return redirect()->route('student.dashboard')
                ->with('success', 'Profile completed successfully. Welcome!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to complete student profile: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save profile. Please try again.');
        }
    }
}
