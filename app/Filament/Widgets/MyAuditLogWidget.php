<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MyAuditLogWidget extends BaseWidget
{
    protected static ?string $heading = 'Aktivitas Terakhir Saya (Tambah/Ubah/Hapus Data)';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AuditLog::query()->where('user_id', auth()->id())->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s'),

                Tables\Columns\TextColumn::make('aksi')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'created' => 'Tambah',
                        'updated' => 'Ubah',
                        'deleted' => 'Hapus',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modul')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('deskripsi')
                    ->label('Detail')
                    ->limit(50),
            ])
            ->paginated(false);
    }
}
