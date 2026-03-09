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
     * Column name aliases — maps various header names to our internal field names.
     * Keys are lowercase/trimmed versions of possible header names.
     */
    protected array $columnAliases = [
        // student_number
        'student_number' => 'student_number',
        'student' => 'student_number',
        'student #' => 'student_number',
        'student#' => 'student_number',
        'student no' => 'student_number',
        'student no.' => 'student_number',
        'student_no' => 'student_number',
        'studentno' => 'student_number',
        'student id' => 'student_number',
        'student_id' => 'student_number',
        'id' => 'student_number',
        'id no' => 'student_number',
        'id no.' => 'student_number',
        'studno' => 'student_number',

        // last_name
        'last_name' => 'last_name',
        'last name' => 'last_name',
        'lastname' => 'last_name',
        'surname' => 'last_name',
        'lname' => 'last_name',

        // first_name
        'first_name' => 'first_name',
        'first name' => 'first_name',
        'firstname' => 'first_name',
        'given name' => 'first_name',
        'given_name' => 'first_name',
        'givenname' => 'first_name',
        'fname' => 'first_name',

        // middle_name
        'middle_name' => 'middle_name',
        'middle name' => 'middle_name',
        'middlename' => 'middle_name',
        'mname' => 'middle_name',
        'mi' => 'middle_name',
        'm.i.' => 'middle_name',

        // year_level
        'year_level' => 'year_level',
        'year level' => 'year_level',
        'yearlevel' => 'year_level',
        'yr' => 'year_level',
        'year' => 'year_level',
        'yr level' => 'year_level',
        'yrlevel' => 'year_level',
        'level' => 'year_level',

        // course
        'course_code' => 'course_code',
        'course code' => 'course_code',
        'coursecode' => 'course_code',
        'course' => 'course_code',
        'course_name' => 'course_code',
        'course name' => 'course_code',
        'program' => 'course_code',

        // section
        'section_name' => 'section',
        'section name' => 'section',
        'section' => 'section',
        'sec' => 'section',
    ];

    /**
     * Import students from Excel/CSV file.
     */
    public function import(string $filePath, int $acadId): array
    {
        $this->errors = [];
        $this->inserted = 0;
        $this->updated = 0;
        $this->failed = 0;

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
                    $this->processRow($rowNumber + 2, $row);
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
     * Read and parse the file, auto-detecting column mapping from headers.
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
     * Map raw headers to internal field names using aliases.
     * Returns an array where key = column index, value = internal field name.
     */
    protected function mapHeaders(array $rawHeaders): array
    {
        $mapping = [];

        // Log raw headers for debugging
        Log::info("Import raw headers: " . json_encode($rawHeaders));

        foreach ($rawHeaders as $index => $header) {
            if ($header === null)
                continue;

            $normalized = strtolower(trim((string) $header));
            // Remove special chars like *, #, arrows, etc.
            $normalized = preg_replace('/[^a-z0-9\s_.-]/', '', $normalized);
            $normalized = trim($normalized);

            Log::info("Import header [{$index}]: raw='{$header}', normalized='{$normalized}'");

            if (isset($this->columnAliases[$normalized])) {
                $fieldName = $this->columnAliases[$normalized];
                // Only map the first occurrence of each field
                if (!in_array($fieldName, $mapping)) {
                    $mapping[$index] = $fieldName;
                }
            }
        }

        // Validate that required fields are mapped
        $mappedFields = array_values($mapping);
        if (!in_array('student_number', $mappedFields)) {
            throw new \Exception("Could not find a 'Student Number' column. Raw headers found: " . json_encode($rawHeaders));
        }

        if (!in_array('course_code', $mappedFields)) {
            throw new \Exception("Could not find a 'Course' column. Expected headers like: Course, Course Code, Program");
        }

        Log::info("Import column mapping: " . json_encode($mapping));

        return $mapping;
    }

    /**
     * Convert a raw data row to our internal format using the column mapping.
     */
    protected function mapRow(array $row, array $columnMapping): array
    {
        $mapped = [];

        foreach ($columnMapping as $index => $fieldName) {
            $mapped[$fieldName] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $mapped;
    }

    /**
     * Read Excel file using maatwebsite/excel.
     * Auto-detects the header row by scanning for recognizable column names.
     */
    protected function readExcelFile(string $filePath): Collection
    {
        try {
            $excelFacade = '\\Maatwebsite\\Excel\\Facades\\Excel';
            if (!class_exists($excelFacade)) {
                throw new \Exception("Excel package (maatwebsite/excel) is not installed. Please run: composer require maatwebsite/excel");
            }

            $data = $excelFacade::toArray([], $filePath);
            $allRows = collect($data[0] ?? []);

            if ($allRows->isEmpty()) {
                throw new \Exception("The file is empty.");
            }

            // Auto-detect header row — scan first 10 rows for one containing recognizable columns
            $headerRowIndex = null;
            $columnMapping = null;

            foreach ($allRows->take(10) as $index => $row) {
                if (!is_array($row))
                    continue;

                // Check if this row has at least 3 non-null cells (likely a header, not a title)
                $nonNullCount = count(array_filter($row, fn($cell) => $cell !== null && trim((string) $cell) !== ''));
                if ($nonNullCount < 3)
                    continue;

                try {
                    $mapping = $this->mapHeaders($row);
                    // If we got here, this row has valid headers
                    $headerRowIndex = $index;
                    $columnMapping = $mapping;
                    Log::info("Import: Found header row at index {$index}");
                    break;
                } catch (\Exception $e) {
                    // Not a valid header row, continue scanning
                    continue;
                }
            }

            if ($headerRowIndex === null || $columnMapping === null) {
                throw new \Exception("Could not find a valid header row in the first 10 rows of the file. Make sure your file has columns like: Student #, Course, Section, Yr");
            }

            // Data rows start after the header row
            return $allRows->slice($headerRowIndex + 1)->map(function ($row) use ($columnMapping) {
                return $this->mapRow($row, $columnMapping);
            })->filter(function ($row) {
                return !empty($row['student_number']);
            })->values();
        } catch (\Exception $e) {
            throw new \Exception("Failed to read Excel file: " . $e->getMessage());
        }
    }

    /**
     * Read CSV file.
     */
    protected function readCsvFile(string $filePath): Collection
    {
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \Exception("Failed to open CSV file.");
        }

        // Read headers
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            fclose($handle);
            throw new \Exception("The CSV file is empty or has no headers.");
        }

        $columnMapping = $this->mapHeaders($rawHeaders);

        // Read data rows
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $mapped = $this->mapRow($row, $columnMapping);
            if (!empty($mapped['student_number'])) {
                $rows[] = $mapped;
            }
        }

        fclose($handle);

        return collect($rows);
    }

    /**
     * Extract section letter from values like "BSHM-B", "BSBAFM-A", or just "A", "B".
     */
    protected function extractSectionLetter(?string $sectionValue): ?string
    {
        if (empty($sectionValue)) {
            return null;
        }

        $sectionValue = trim($sectionValue);

        // If it contains a dash, take the part after the last dash (e.g., "BSHM-B" → "B")
        if (str_contains($sectionValue, '-')) {
            $parts = explode('-', $sectionValue);
            $letter = trim(end($parts));
            return strtoupper($letter) ?: null;
        }

        // If it's already just a letter or short string, use it as-is
        return strtoupper($sectionValue);
    }

    /**
     * Process a single row from the import file.
     */
    protected function processRow(int $rowNumber, array $row): void
    {
        // Validate required fields
        if (empty($row['student_number'])) {
            throw new \Exception("Student number is required");
        }

        // Clean student number — remove any decimals from Excel (e.g., "47862022.0" → "47862022")
        $studentNumber = $row['student_number'];
        if (is_numeric($studentNumber)) {
            $studentNumber = (string) intval($studentNumber);
        }

        // Extract names — if no first/last name columns, try to parse from student_number context
        $firstName = $row['first_name'] ?? '';
        $lastName = $row['last_name'] ?? '';
        $middleName = $row['middle_name'] ?? null;

        if (empty($firstName) && empty($lastName)) {
            throw new \Exception("Student name is required (first name and/or last name columns)");
        }

        // Resolve course
        $course = $this->resolveCourse($row);
        if (!$course) {
            throw new \Exception("Course not found: " . ($row['course_code'] ?? 'N/A'));
        }

        // Extract year level
        $yearLevel = $row['year_level'] ?? null;
        if (!empty($yearLevel)) {
            // Extract just the number (e.g., "C4" → "4", "Year 3" → "3")
            if (preg_match('/(\d+)/', (string) $yearLevel, $matches)) {
                $yearLevel = $matches[1];
            }
        }
        if (empty($yearLevel)) {
            throw new \Exception("Year level is required");
        }

        // Resolve section (extract letter from values like "BSHM-B")
        $sectionId = null;
        $sectionLetter = $this->extractSectionLetter($row['section'] ?? null);
        if ($sectionLetter) {
            $section = Section::firstOrCreate(
                [
                    'section_name' => $sectionLetter,
                    'course_id' => $course->course_id,
                ],
                [
                    'section_code' => $sectionLetter,
                    'year_level' => $yearLevel,
                ]
            );
            $sectionId = $section->section_id;
        }

        // Upsert student — only update name fields, leave profile fields for students
        $student = Student::updateOrCreate(
            ['student_number' => $studentNumber],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'status' => 'enrolled',
            ]
        );

        $wasRecentlyCreated = $student->wasRecentlyCreated;

        // Create or update enrollment for the specified academic term
        EnrolledStudent::updateOrCreate(
            [
                'student_number' => $student->student_number,
                'acad_id' => $this->academicCalendar->calendar_id,
            ],
            [
                'course_id' => $course->course_id,
                'section_id' => $sectionId,
                'year_level' => $yearLevel,
                'enrollment_status' => 'enrolled',
                'enrollment_date' => now(),
                'academic_year' => $this->academicCalendar->academic_year,
            ]
        );

        if ($wasRecentlyCreated) {
            $this->inserted++;
        } else {
            $this->updated++;
        }
    }

    /**
     * Resolve course from row data.
     */
    protected function resolveCourse(array $row): ?Course
    {
        $courseValue = $row['course_code'] ?? null;

        if (empty($courseValue)) {
            return null;
        }

        $courseValue = trim($courseValue);

        // Try exact match by course_code
        $course = Course::where('course_code', $courseValue)->first();
        if ($course)
            return $course;

        // Try exact match by course_name
        $course = Course::where('course_name', $courseValue)->first();
        if ($course)
            return $course;

        // Try case-insensitive match
        $course = Course::whereRaw('LOWER(course_code) = ?', [strtolower($courseValue)])->first();
        if ($course)
            return $course;

        // Auto-create the course if not found
        $course = Course::create([
            'course_code' => strtoupper($courseValue),
            'course_name' => strtoupper($courseValue),
        ]);

        Log::info("Import: Auto-created course '{$courseValue}' (course_id: {$course->course_id})");

        return $course;
    }

    /**
     * Get error report as CSV content.
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
