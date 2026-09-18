<?php

namespace App\Filament\Resources\ProductVersions\Pages;

use App\Filament\Resources\ProductVersions\ProductVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductVersion extends EditRecord
{
    protected static string $resource = ProductVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        ProductVersionResource::processUploadedArchive(
            $this->record,
            $this->data['release_archive'] ?? null
        );
    }
}
