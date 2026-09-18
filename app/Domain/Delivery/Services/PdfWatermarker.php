<?php

namespace App\Domain\Delivery\Services;

use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class PdfWatermarker
{
    /**
     * Stamp buyer email and order reference on every page footer of a PDF (@research.md §5.1 point 4).
     */
    public function watermark(string $sourcePdfPathOrContent, string $buyerEmail, string $orderNumber): string
    {
        $tempSource = null;
        if (! file_exists($sourcePdfPathOrContent)) {
            $tempSource = tempnam(sys_get_temp_dir(), 'pdf_src_');
            file_put_contents($tempSource, $sourcePdfPathOrContent);
            $filePath = $tempSource;
        } else {
            $filePath = $sourcePdfPathOrContent;
        }

        try {
            $pdf = new Fpdi();
            $pdf->SetCompression(false);
            $pageCount = $pdf->setSourceFile($filePath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Footer banner stamping: buyer email + order reference + timestamp
                $pdf->SetFont('Helvetica', '', 8);
                $pdf->SetTextColor(100, 116, 139); // slate-500
                $pdf->SetXY(10, $size['height'] - 10);

                $stampedDate = date('Y-m-d H:i T');
                $text = "Licensed to {$buyerEmail} • Order #{$orderNumber} • {$stampedDate}";
                
                // Convert to ASCII / ISO-8859-1 for FPDF compatibility
                $convertedText = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text) ?: $text;
                $pdf->Cell($size['width'] - 20, 5, $convertedText, 0, 0, 'C');
            }

            return $pdf->Output('S');
        } catch (\Throwable $e) {
            Log::error('PdfWatermarker error: '.$e->getMessage());
            throw $e;
        } finally {
            if ($tempSource && file_exists($tempSource)) {
                @unlink($tempSource);
            }
        }
    }
}
