<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use App\Models\Course;
use App\Models\Section;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings management page.
     */
    public function index(): Response
    {
        // Get all academic calendars
        $academicCalendars = AcademicCalendar::orderBy('start_date', 'desc')
            ->get()
            ->map(function ($calendar) {
                return [
                    'calendar_id' => $calendar->calendar_id,
                    'academic_year' => $calendar->academic_year,
                    'semester' => $calendar->semester,
                    'start_date' => $calendar->start_date->format('Y-m-d'),
                    'end_date' => $calendar->end_date->format('Y-m-d'),
                    'status' => $calendar->status,
                    'display_label' => $calendar->display_label,
                    'enrollments_count' => $calendar->enrolledStudents()->count(),
                ];
            });

        // Get all courses with section counts
        $courses = Course::withCount('sections')
            ->orderBy('course_name')
            ->get()
            ->map(function ($course) {
                return [
                    'course_id' => $course->course_id,
                    'course_code' => $course->course_code,
                    'course_name' => $course->course_name,
                    'description' => $course->description,
                    'sections_count' => $course->sections_count,
                ];
            });

        // Get all sections with course and calendar info
        $sections = Section::with(['course', 'academicCalendar'])
            ->orderBy('section_name')
            ->get()
            ->map(function ($section) {
                return [
                    'section_id' => $section->section_id,
                    'section_code' => $section->section_code,
                    'section_name' => $section->section_name,
                    'course_id' => $section->course_id,
                    'course_code' => $section->course->course_code ?? null,
                    'course_name' => $section->course->course_name ?? null,
                    'calendar_id' => $section->calendar_id,
                    'academic_year' => $section->academicCalendar->academic_year ?? null,
                    'semester' => $section->academicCalendar->semester ?? null,
                ];
            });

        return Inertia::render('Admin/Settings/Index', [
            'academicCalendars' => $academicCalendars,
            'courses' => $courses,
            'sections' => $sections,
        ]);
    }
}

