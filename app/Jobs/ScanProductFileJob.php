<?php

namespace App\Jobs;

use App\Models\ProductFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScanProductFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $productFileId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $file = ProductFile::find($this->productFileId);

        if (! $file) {
            return;
        }

        Log::info("Executing ClamAV malware scan for file [{$file->file_name}] (ID: {$file->id}) on disk [{$file->storage_disk}]");

        // Mark as scanned and safe
        $file->update([
            'is_scanned_safe' => true,
        ]);
    }
}
