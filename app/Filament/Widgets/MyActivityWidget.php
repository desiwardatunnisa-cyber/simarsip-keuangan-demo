<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use App\Models\LoginSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * "My Activity" — versi Dashboard dari tabel Monitoring Staff (kolom,
 * badge status, aksi "Lihat Detail" dengan modal, pencarian, filter, dan
 * polling realtime yang sama persis), tapi dibatasi hanya ke sesi login
 * milik user yang sedang login (tanpa kolom Staff/Role karena selalu diri
 * sendiri).
 */
class MyActivityWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    // Realtime tanpa perlu refresh manual, sama seperti Monitoring Staff.
    protected static ?string $pollingInterval = '30s';

    public function getTableHeading(): string
    {
        return 'Aktivitas Saya';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LoginSession::query()
                    ->where('user_id', auth()->id())
                    ->latest('login_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('login_at')
                    ->label('Jam Masuk')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('logout_at')
                    ->label('Jam Keluar')
                    ->dateTime('d M Y H:i:s')
                    ->placeholder('Masih Online')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->state(function ($record) {
                        if ($record->isOnline()) {
                            return 'Online';
                        }
                        if (method_exists($record, 'isIdle') && $record->isIdle()) {
                            return 'Idle (Session Timeout)';
                        }
                        return 'Offline';
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Online' => 'success',
                        'Idle (Session Timeout)' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('durasi')
                    ->label('Durasi')
                    ->state(fn ($record) => $record->durasi()),

                Tables\Columns\TextColumn::make('access_logs_count')
                    ->label('Jml Akses Halaman')
                    ->counts('accessLogs')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('online')
                    ->label('Sedang Online')
                    ->query(fn ($query) => $query->whereNull('logout_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Aktivitas Saya')
                    ->modalWidth('4xl')
                    ->modalContent(fn ($record) => view('filament.pages.partials.detail-akses', [
                        'session' => $record,
                        'accessLogs' => $record->accessLogs()->latest()->limit(50)->get(),
                        'auditLogs' => AuditLog::where('user_id', $record->user_id)
                            ->whereBetween('created_at', [$record->login_at, $record->logout_at ?? now()])
                            ->latest()
                            ->limit(50)
                            ->get(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->actionsColumnLabel('Aksi')
            ->defaultSort('login_at', 'desc')
            ->paginated([10, 25, 50])
            ->poll('30s');
    }
}
