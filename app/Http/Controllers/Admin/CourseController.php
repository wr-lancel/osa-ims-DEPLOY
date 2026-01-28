<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index()
    {
        $courses = Course::orderBy('course_name')->get();
        return response()->json($courses);
    }

    /**
     * Store a newly created course.
     */
    public function store(StoreCourseRequest $request)
    {
        try {
            $course = Course::create($request->validated());

            Log::info("Course created: {$course->course_code} by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully.',
                'course' => $course,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create course: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create course: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to create course. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified course.
     */
    public function update(UpdateCourseRequest $request, Course $course)
    {
        try {
            $course->update($request->validated());

            Log::info("Course updated: {$course->course_code} by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully.',
                'course' => $course,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update course: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update course. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course)
    {
        try {
            // Check if course has sections
            if ($course->sections()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete course. It has associated sections.',
                ], 422);
            }

            // Check if course has enrollments
            if ($course->enrolledStudents()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete course. It has student enrollments.',
                ], 422);
            }

            $courseCode = $course->course_code;
            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            $course->delete();

            Log::info("Course deleted: {$courseCode} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete course: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course. Please try again.',
            ], 500);
        }
    }
}

