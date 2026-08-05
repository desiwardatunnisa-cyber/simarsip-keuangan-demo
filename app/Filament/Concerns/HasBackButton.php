<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;

trait HasBackButton
{
    /**
     * Tombol "Back" konsisten untuk halaman bertingkat (Detail, Edit, Tambah, dsb).
     * Selalu mengarah ke halaman index resource terkait.
     */
    protected function backButtonAction(): Action
    {
        return Action::make('kembali')
            ->label('Back')
            ->color('gray')
            ->url(fn () => static::getResource()::getUrl('index'));
    }
}
