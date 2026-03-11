<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/admin/students/export/pdf', 'GET', [
    'year_level' => '1',
    'course_id' => '1' // Assuming 1 is BSCS or similar
]);

$controller = app(\App\Http\Controllers\Admin\StudentRecordController::class);

try {
    $response = $controller->exportPdf($request);
    echo "Export successful. Response class: " . get_class($response) . "\n";
} catch (\Exception $e) {
    echo "Export failed: " . $e->getMessage() . "\n";
}
