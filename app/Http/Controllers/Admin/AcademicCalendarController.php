<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAcademicCalendarRequest;
use App\Http\Requests\Admin\UpdateAcademicCalendarRequest;
use App\Models\AcademicCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicCalendarController extends Controller
{
    /**
     * Display a listing of academic calendars.
     */
    public function index()
    {
        $calendars = AcademicCalendar::orderBy('start_date', 'desc')->get();
        return response()->json($calendars);
    }

    /**
     * Store a newly created academic calendar.
     */
    public function store(StoreAcademicCalendarRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // If setting as active, deactivate all other calendars first
            if (isset($data['status']) && $data['status'] === 'active') {
                AcademicCalendar::where('status', 'active')->update(['status' => 'completed']);
            }

            $calendar = AcademicCalendar::create($data);

            DB::commit();

            Log::info("Academic calendar created: {$calendar->academic_year} (ID: {$calendar->calendar_id}) by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Academic calendar created successfully.',
                'calendar' => $calendar,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create academic calendar: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create academic calendar: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to create academic calendar. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified academic calendar.
     */
    public function update(UpdateAcademicCalendarRequest $request, AcademicCalendar $calendar)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // If setting as active, deactivate all other calendars first
            if (isset($data['status']) && $data['status'] === 'active' && $calendar->status !== 'active') {
                AcademicCalendar::where('status', 'active')
                    ->where('calendar_id', '!=', $calendar->calendar_id)
                    ->update(['status' => 'completed']);
            }

            $calendar->update($data);

            DB::commit();

            Log::info("Academic calendar updated: {$calendar->academic_year} (ID: {$calendar->calendar_id}) by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Academic calendar updated successfully.',
                'calendar' => $calendar,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update academic calendar: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update academic calendar. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified academic calendar.
     */
    public function destroy(AcademicCalendar $calendar)
    {
        try {
            // Check if calendar has enrollments
            if ($calendar->enrolledStudents()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete academic calendar. It has student enrollments.',
                ], 422);
            }

            $academicYear = $calendar->academic_year;
            $calendarId = $calendar->calendar_id;
            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            $calendar->delete();

            Log::info("Academic calendar deleted: {$academicYear} (ID: {$calendarId}) by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Academic calendar deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete academic calendar: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete academic calendar. Please try again.',
            ], 500);
        }
    }
}

