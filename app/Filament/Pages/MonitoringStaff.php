<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\LoginSession;
use App\Filament\Widgets\MonitoringStaffStatsOverview;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class MonitoringStaff extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationLabel = 'Monitoring Staff';

    protected static ?string $navigationGroup = 'Administrasi';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.monitoring-staff';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MonitoringStaffStatsOverview::class,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Monitoring Staff')
            ->query(LoginSession::query()->with(['user', 'accessLogs'])->whereHas('user')->visibleTo(auth()->user())->latest('login_at'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable()
                    ->default('(user terhapus)'),

                Tables\Columns\TextColumn::make('user.role')
                    ->label('Role')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->user?->isAdminIT() => 'Super Admin',
                        $record->user?->isKabag() => 'Kepala Bagian',
                        $record->user?->isAdminBagian() => 'Admin Bagian',
                        $state === 'admin' => 'Admin', // fallback kalau bagian belum di-set
                        default => 'Staff',
                    })
                    ->color(fn ($state) => $state === 'admin' ? 'danger' : 'info'),

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
                        if ($record->isIdle()) {
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
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Staff')
                    ->relationship(
                        'user',
                        'name',
                        fn ($query) => auth()->user()->isAdminIT() || auth()->user()->isKabag()
                            ? $query
                            : (auth()->user()->isAdminBagian()
                                ? $query->where('departemen', auth()->user()->departemen)
                                : $query->where('id', auth()->id()))
                    ),

                Tables\Filters\Filter::make('online')
                    ->label('Sedang Online')
                    ->query(fn ($query) => $query->whereNull('logout_at')),
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => 'Detail Aktivitas — ' . ($record->user?->name ?? 'User Terhapus'))
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
            ->poll('30s');
    }
}