<?php

namespace App\Services;

use App\Models\Student;
use App\Models\EnrolledStudent;
use App\Models\Course;
use App\Models\Section;
use App\Models\AcademicCalendar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class StudentImportService
{
    protected array $errors = [];
    protected int $inserted = 0;
    protected int $updated = 0;
    protected int $failed = 0;
    protected ?AcademicCalendar $academicCalendar = null;

    /**
     * Import students from Excel/CSV file.
     *
     * @param string $filePath
     * @param int $acadId The academic calendar ID for this import
     * @return array{success: bool, inserted: int, updated: int, failed: int, errors: array}
     */
    public function import(string $filePath, int $acadId): array
    {
        // Reset counters
        $this->errors = [];
        $this->inserted = 0;
        $this->updated = 0;
        $this->failed = 0;

        // Get the specified academic calendar
        $this->academicCalendar = AcademicCalendar::find($acadId);

        if (!$this->academicCalendar) {
            return [
                'success' => false,
                'inserted' => 0,
                'updated' => 0,
                'failed' => 0,
                'errors' => ['Invalid academic calendar selected.'],
            ];
        }

        try {
            $rows = $this->readFile($filePath);

            DB::beginTransaction();

            foreach ($rows as $rowNumber => $row) {
                try {
                    $this->processRow($rowNumber + 2, $row); // +2 because row 1 is header, and arrays are 0-indexed
                } catch (\Exception $e) {
                    $this->errors[] = [
                        'row' => $rowNumber + 2,
                        'student_number' => $row['student_number'] ?? 'N/A',
                        'error' => $e->getMessage(),
                    ];
                    $this->failed++;
                    Log::error("Student import error on row " . ($rowNumber + 2) . ": " . $e->getMessage());
                }
            }

            DB::commit();

            Log::info("Student import completed for term {$acadId}. Inserted: {$this->inserted}, Updated: {$this->updated}, Failed: {$this->failed}");

            return [
                'success' => true,
                'inserted' => $this->inserted,
                'updated' => $this->updated,
                'failed' => $this->failed,
                'errors' => $this->errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Student import failed: " . $e->getMessage());

            return [
                'success' => false,
                'inserted' => $this->inserted,
                'updated' => $this->updated,
                'failed' => $this->failed,
                'errors' => array_merge($this->errors, ['General error: ' . $e->getMessage()]),
            ];
        }
    }

    /**
     * Read and parse the Excel/CSV file.
     *
     * @param string $filePath
     * @return Collection
     */
    protected function readFile(string $filePath): Collection
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->readExcelFile($filePath);
        } else {
            return $this->readCsvFile($filePath);
        }
    }

    /**
     * Read Excel file using maatwebsite/excel.
     *
     * @param string $filePath
     * @return Collection
     */
    protected function readExcelFile(string $filePath): Collection
    {
        try {
            // Check if Excel package is installed
            $excelFacade = '\\Maatwebsite\\Excel\\Facades\\Excel';
            if (!class_exists($excelFacade)) {
                throw new \Exception("Excel package (maatwebsite/excel) is not installed. Please run: composer require maatwebsite/excel");
            }
            
            // Use string variable for facade class to avoid IDE type errors when package isn't installed
            $data = $excelFacade::toArray([], $filePath);
            $rows = collect($data[0] ?? []);

            // Skip header row
            if ($rows->isNotEmpty()) {
                $rows = $rows->slice(1);
            }

            // Map to associative array using expected headers
            return $rows->map(function ($row) {
                return [
                    'student_number' => $row[0] ?? null,
                    'first_name' => $row[1] ?? null,
                    'last_name' => $row[2] ?? null,
                    'middle_name' => $row[3] ?? null,
                    'email' => $row[4] ?? null,
                    'phone' => $row[5] ?? null,
                    'course_code' => $row[6] ?? null,
                    'course_name' => $row[7] ?? null,
                    'section_name' => $row[8] ?? null,
                    'year_level' => $row[9] ?? null,
                ];
            })->filter(function ($row) {
                // Filter out completely empty rows
                return !empty($row['student_number']);
            });
        } catch (\Exception $e) {
            throw new \Exception("Failed to read Excel file: " . $e->getMessage());
        }
    }

    /**
     * Read CSV file.
     *
     * @param string $filePath
     * @return Collection
     */
    protected function readCsvFile(string $filePath): Collection
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception("Failed to open CSV file.");
        }

        // Read header
        $headers = fgetcsv($handle);

        // Read data rows
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $row);
        }

        fclose($handle);

        return collect($rows)->filter(function ($row) {
            return !empty($row['student_number'] ?? null);
        });
    }

    /**
     * Process a single row from the import file.
     *
     * @param int $rowNumber
     * @param array $row
     * @return void
     * @throws \Exception
     */
    protected function processRow(int $rowNumber, array $row): void
    {
        // Validate required fields
        if (empty($row['student_number'])) {
            throw new \Exception("Student number is required");
        }

        if (empty($row['year_level'])) {
            throw new \Exception("Year level is required");
        }

        // Resolve course
        $course = $this->resolveCourse($row);
        if (!$course) {
            throw new \Exception("Course not found (course_code: " . ($row['course_code'] ?? 'N/A') . ", course_name: " . ($row['course_name'] ?? 'N/A') . ")");
        }

        // Resolve section (optional) - now includes year_level matching
        $section = null;
        if (!empty($row['section_name'])) {
            $section = $this->resolveSection($course, $row);
        }

        // Upsert student
        $student = Student::updateOrCreate(
            ['student_number' => $row['student_number']],
            [
                'first_name' => $row['first_name'] ?? '',
                'last_name' => $row['last_name'] ?? '',
                'middle_name' => $row['middle_name'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'status' => 'active',
            ]
        );

        $wasRecentlyCreated = $student->wasRecentlyCreated;

        // Create or update enrollment for the specified academic term
        $enrollment = EnrolledStudent::updateOrCreate(
            [
                'student_number' => $student->student_number,
                'acad_id' => $this->academicCalendar->calendar_id,
            ],
            [
                'course_id' => $course->course_id,
                'section_id' => $section?->section_id,
                'year_level' => $row['year_level'],
                'enrollment_status' => 'active',
                'enrollment_date' => now(),
                'academic_year' => $this->academicCalendar->academic_year,
            ]
        );

        // Count based on student creation, not enrollment
        if ($wasRecentlyCreated) {
            $this->inserted++;
        } else {
            $this->updated++;
        }
    }

    /**
     * Resolve course from row data.
     *
     * @param array $row
     * @return Course|null
     */
    protected function resolveCourse(array $row): ?Course
    {
        // Prefer course_code, fallback to course_name
        if (!empty($row['course_code'])) {
            $course = Course::where('course_code', $row['course_code'])->first();
            if ($course) {
                return $course;
            }
        }

        if (!empty($row['course_name'])) {
            $course = Course::where('course_name', $row['course_name'])->first();
            if ($course) {
                return $course;
            }
        }

        return null;
    }

    /**
     * Resolve section from row data.
     * Matches by course_id + year_level + section_name
     *
     * @param Course $course
     * @param array $row
     * @return Section|null
     */
    protected function resolveSection(Course $course, array $row): ?Section
    {
        if (empty($row['section_name'])) {
            return null;
        }

        $query = Section::where('course_id', $course->course_id)
            ->where('section_name', $row['section_name']);

        // If year_level is provided, also match by year_level
        if (!empty($row['year_level'])) {
            $query->where(function ($q) use ($row) {
                $q->where('year_level', $row['year_level'])
                  ->orWhereNull('year_level'); // Also allow sections without year_level set
            });
        }

        return $query->first();
    }

    /**
     * Get error report as CSV content.
     *
     * @return string
     */
    public function getErrorReport(): string
    {
        $csv = "Row,Student Number,Error\n";

        foreach ($this->errors as $error) {
            $csv .= sprintf(
                "%s,%s,\"%s\"\n",
                $error['row'] ?? '',
                $error['student_number'] ?? '',
                str_replace('"', '""', $error['error'] ?? '')
            );
        }

        return $csv;
    }
}
