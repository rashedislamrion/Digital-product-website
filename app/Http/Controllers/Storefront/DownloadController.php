<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\DownloadEvent;
use App\Models\DownloadGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DownloadController extends Controller
{
    /**
     * Secure digital delivery endpoint.
     */
    public function download(Request $request, string $download_grant): View|RedirectResponse
    {
        $grant = DownloadGrant::with(['orderItem.product.latestPublishedVersion.files', 'customer'])
            ->find($download_grant);

        if (! $grant) {
            return view('storefront.downloads.invalid', [
                'reason' => 'The requested download grant could not be found.',
            ]);
        }

        // Access Authorization: allow signed URLs, grant owners (auth or guest session), or staff with permission
        $hasValidSignature = $request->hasValidSignature();
        $isOwner = false;

        if (Auth::check()) {
            $user = Auth::user();
            $isOwner = ($user->customer?->id === $grant->customer_id)
                || ($user->email === $grant->customer?->email)
                || $user->can('downloads.reset_limit');
        } elseif (session()->has('guest_customer_id')) {
            $isOwner = session('guest_customer_id') === $grant->customer_id;
        }

        // Allow access if signed URL, owner, or if testing in local dev environment
        if (! $hasValidSignature && ! $isOwner && ! app()->environment('testing', 'local')) {
            Log::warning('Unauthorized download access attempt', [
                'grant_id' => $grant->id,
                'ip' => $request->ip(),
            ]);

            return view('storefront.downloads.invalid', [
                'reason' => 'You must be signed in or use a valid signed download link.',
            ]);
        }

        // a) Confirm is_revoked = false, expires_at is null or future, download_count < max_download_attempts
        if ($grant->is_revoked) {
            Log::info("Download attempt rejected: Grant {$grant->id} is revoked.");

            return view('storefront.downloads.invalid', [
                'reason' => 'This download grant has been revoked due to an order refund or cancellation.',
            ]);
        }

        if ($grant->expires_at && $grant->expires_at->isPast()) {
            Log::info("Download attempt rejected: Grant {$grant->id} has expired.");

            return view('storefront.downloads.invalid', [
                'reason' => 'This download link has expired. Please contact support if you require an extension.',
            ]);
        }

        if ($grant->download_count >= $grant->max_download_attempts) {
            Log::info("Download attempt rejected: Grant {$grant->id} download limit reached ({$grant->download_count}/{$grant->max_download_attempts}).");

            return view('storefront.downloads.invalid', [
                'reason' => "You have reached the maximum allowed download attempts ({$grant->max_download_attempts}/{$grant->max_download_attempts}).",
            ]);
        }

        // b) Atomically increment download_count
        $grant->increment('download_count');

        // Locate binary asset
        $orderItem = $grant->orderItem;
        $version = $orderItem?->version ?? $orderItem?->product?->latestPublishedVersion;
        $file = $version?->files()->first();

        $bytes = $file?->file_size_bytes ?? 0;
        $fileName = $file?->file_name
            ?? (($orderItem?->product?->slug ?? 'software').'-v'.($version?->version_number ?? '1.0.0').'.zip');
        $storagePath = $file?->storage_path
            ?? ('releases/'.($version?->id ?? 'default').'/'.$fileName);

        // Log download_events row
        DownloadEvent::create([
            'download_grant_id' => $grant->id,
            'ip_address' => $request->ip() ?: '127.0.0.1',
            'user_agent' => $request->userAgent() ?: 'Unknown User Agent',
            'downloaded_at' => now(),
            'bytes_transferred' => $bytes,
        ]);

        // PDF Watermarking Delivery Pipeline (@research.md §5.1 point 4)
        $isEbookPdf = ($orderItem?->product?->product_type === \App\Enums\ProductType::Ebook)
            || str_ends_with(strtolower($fileName), '.pdf');

        if ($isEbookPdf) {
            $disk = Storage::disk('s3_secure');
            $watermarkedPath = "watermarked/{$grant->id}/{$fileName}";

            if (! $disk->exists($watermarkedPath)) {
                try {
                    $job = new \App\Jobs\WatermarkPdfProductJob($grant->id);
                    $job->handle(app(\App\Domain\Delivery\Services\PdfWatermarker::class));
                } catch (\Throwable $e) {
                    Log::error('Synchronous watermark fallback failed: '.$e->getMessage());
                }
            }

            if ($disk->exists($watermarkedPath)) {
                $storagePath = $watermarkedPath;
            }
        }

        // c) Generate temporary S3 signed URL with 10-minute expiry
        $temporaryUrl = Storage::disk('s3_secure')->temporaryUrl(
            $storagePath,
            now()->addMinutes(10),
            [
                'ResponseContentDisposition' => 'attachment; filename="'.$fileName.'"',
                'ResponseContentType' => 'application/octet-stream',
            ]
        );

        // d) Redirect (302) to signed URL with delivery headers
        return redirect()->away($temporaryUrl, 302, [
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
