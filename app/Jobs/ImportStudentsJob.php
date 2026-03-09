<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\StudentImportService;
use App\Models\User;
use App\Notifications\ImportCompletedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Give it plenty of time for large files.
     */
    public $timeout = 600;

    protected $filePath;
    protected $acadId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $acadId, int $userId)
    {
        $this->filePath = $filePath;
        $this->acadId = $acadId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(StudentImportService $importService): void
    {
        Log::info("Starting background import job for file: {$this->filePath}, acadId: {$this->acadId}, userId: {$this->userId}");

        try {
            $absolutePath = Storage::path($this->filePath);

            // Run the import service processing
            $result = $importService->import($absolutePath, $this->acadId);

            Log::info("Background import job completed", $result);

            // Clean up the temporary file after successful import
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }

            // Optional: Notify the user that their import finished (if you have notifications set up)
            $user = User::find($this->userId);
            if ($user && method_exists($user, 'notify')) {
                // If you later add an ImportCompletedNotification class, you can uncomment this
                // $user->notify(new ImportCompletedNotification($result));
            }

        } catch (Throwable $e) {
            Log::error("Background import job failed: " . $e->getMessage());
            
            // Clean up the file even on failure
            if (Storage::exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
            
            throw $e;
        }
    }
}
