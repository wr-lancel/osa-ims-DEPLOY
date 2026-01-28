<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Models\AcademicCalendar;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SectionController extends Controller
{
    /**
     * Display a listing of sections.
     */
    public function index(Request $request)
    {
        $query = Section::with(['course', 'academicCalendar']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('calendar_id')) {
            $query->where('calendar_id', $request->calendar_id);
        }

        $sections = $query->orderBy('section_name')->get();
        return response()->json($sections);
    }

    /**
     * Store a newly created section.
     */
    public function store(StoreSectionRequest $request)
    {
        try {
            $section = Section::create($request->validated());

            Log::info("Section created: {$section->section_code} by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Section created successfully.',
                'section' => $section->load(['course', 'academicCalendar']),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create section: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create section. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified section.
     */
    public function update(UpdateSectionRequest $request, Section $section)
    {
        try {
            $section->update($request->validated());

            Log::info("Section updated: {$section->section_code} by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Section updated successfully.',
                'section' => $section->load(['course', 'academicCalendar']),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update section: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update section. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Section $section)
    {
        try {
            // Check if section has enrollments
            if ($section->enrolledStudents()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete section. It has student enrollments.',
                ], 422);
            }

            $sectionCode = $section->section_code;
            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            $section->delete();

            Log::info("Section deleted: {$sectionCode} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Section deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete section: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete section. Please try again.',
            ], 500);
        }
    }
}

