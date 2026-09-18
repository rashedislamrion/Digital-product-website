<?php

namespace App\Jobs;

use App\Domain\Delivery\Services\PdfWatermarker;
use App\Enums\ProductType;
use App\Models\DownloadGrant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class WatermarkPdfProductJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $downloadGrantId,
    ) {}

    /**
     * Execute the watermarking queued job (@research.md §5.1 point 4).
     */
    public function handle(PdfWatermarker $watermarker): void
    {
        $grant = DownloadGrant::with(['orderItem.product', 'orderItem.order', 'orderItem.version.files', 'customer'])
            ->find($this->downloadGrantId);

        if (! $grant || ! $grant->orderItem) {
            Log::warning('WatermarkPdfProductJob: Grant or order item not found', [
                'grant_id' => $this->downloadGrantId,
            ]);

            return;
        }

        $orderItem = $grant->orderItem;
        $product = $orderItem->product;
        $order = $orderItem->order;
        $customer = $grant->customer;

        $version = $orderItem->version ?? $product?->latestPublishedVersion;
        $file = $version?->files()->first();

        $fileName = $file?->file_name
            ?? (($product?->slug ?? 'ebook').'-v'.($version?->version_number ?? '1.0.0').'.pdf');

        // Check if file is PDF or product is Ebook
        $isPdf = str_ends_with(strtolower($fileName), '.pdf')
            || ($product && $product->product_type === ProductType::Ebook);

        if (! $isPdf) {
            return;
        }

        $disk = Storage::disk('s3_secure');
        $watermarkedPath = "watermarked/{$grant->id}/{$fileName}";

        if ($disk->exists($watermarkedPath)) {
            Log::info("WatermarkPdfProductJob: Watermarked file already exists for Grant {$grant->id}");

            return;
        }

        $sourcePath = $file?->storage_path ?? ('releases/'.($version?->id ?? 'default').'/'.$fileName);
        $buyerEmail = $customer?->email ?? 'customer@example.com';
        $orderNumber = $order?->order_number ?? 'ORD-REF';

        try {
            if ($disk->exists($sourcePath)) {
                $sourceContent = $disk->get($sourcePath);
            } else {
                // Generate a starter PDF if source is missing in local dev
                $pdf = new Fpdi();
                $pdf->AddPage();
                $pdf->SetFont('Helvetica', 'B', 16);
                $pdf->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $orderItem->historical_product_title ?: 'Digital Ebook Publication'), 0, 1, 'C');
                $pdf->Ln(10);
                $pdf->SetFont('Helvetica', '', 11);
                $pdf->MultiCell(0, 6, "Official licensed digital copy. Verified purchase by {$buyerEmail}.\nAll rights reserved by DevStore Pro.");
                $sourceContent = $pdf->Output('S');
            }

            $watermarkedContent = $watermarker->watermark($sourceContent, $buyerEmail, $orderNumber);
            $disk->put($watermarkedPath, $watermarkedContent);

            Log::info("WatermarkPdfProductJob: Successfully generated watermarked PDF for Grant {$grant->id} (Order #{$orderNumber})");
        } catch (\Throwable $e) {
            Log::error('WatermarkPdfProductJob: Failed to watermark PDF', [
                'grant_id' => $grant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
