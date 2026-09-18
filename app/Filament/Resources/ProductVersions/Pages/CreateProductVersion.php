<?php

namespace App\Filament\Resources\ProductVersions\Pages;

use App\Filament\Resources\ProductVersions\ProductVersionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductVersion extends CreateRecord
{
    protected static string $resource = ProductVersionResource::class;

    protected function afterCreate(): void
    {
        ProductVersionResource::processUploadedArchive(
            $this->record,
            $this->data['release_archive'] ?? null
        );
    }
}
