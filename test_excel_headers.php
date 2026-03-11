<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Maatwebsite\Excel\Facades\Excel;

$files = [
    'EL - 2nd-Sem AY 2025-2026-List and Summary to sir Torres and BSCS Research Group.xlsx',
    'Official-List-of-Enrollment-2nd-Semester-AY-2025-2026.xlsx'
];

foreach ($files as $filePath) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }
    
    echo "\n--- File: $filePath ---\n";
    try {
        $data = Excel::toArray([], $filePath);
        $sheetInfo = isset($data[0]) ? "Has at least 1 sheet" : "No sheets";
        echo "Status: $sheetInfo\n";
        
        if (isset($data[0])) {
            $count = 0;
            foreach ($data[0] as $i => $row) {
                if ($count >= 15) break; 
                $nonNullCount = count(array_filter($row, function($cell) {
                    return $cell !== null && trim((string)$cell) !== '';
                }));
                // Only print if there's actual data
                if ($nonNullCount > 0) {
                    echo "Row $i (non-null: $nonNullCount): " . json_encode($row) . "\n";
                    $count++;
                }
            }
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
