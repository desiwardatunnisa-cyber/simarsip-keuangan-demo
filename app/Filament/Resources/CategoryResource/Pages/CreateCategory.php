<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    // Tombol "Back" di header dihapus — form ini sudah punya tombol
    // "Cancel" di bawah (bawaan Filament), jadi Back jadi redundan.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
