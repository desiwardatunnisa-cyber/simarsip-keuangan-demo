<?php

namespace App\Filament\Widgets;

use App\Models\LoginSession;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class LoginHistoryWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Riwayat Aktivitas Login';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $user = auth()->user();
                $tahun = $this->filters['tahun'] ?? now()->year;
                $divisi = $this->filters['divisi'] ?? null;

                $query = LoginSession::query()->visibleTo($user)->with('user')
                    ->whereYear('login_at', $tahun);

                if ($divisi && $user->isAdminIT()) {
                    $query->whereHas('user', fn ($q) => $q->where('departemen', $divisi));
                }

                return $query->latest('login_at')->limit(10);
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->visible(fn () => ! auth()->user()->isStaff())
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('login_at')
                    ->label('Tanggal')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('login_at')
                    ->label('Jam')
                    ->time('H:i'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address'),

                Tables\Columns\TextColumn::make('user_agent')
                    ->label('Browser')
                    ->formatStateUsing(fn (?string $state) => self::detectBrowser($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Login')
                    ->state(function (LoginSession $record) {
                        if ($record->isOnline()) return 'Online';
                        if ($record->isTimeout()) return 'Timeout';
                        return 'Selesai';
                    })
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Online' => 'success',
                        'Timeout' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum ada riwayat login')
            ->emptyStateDescription('Riwayat login akan tercatat setiap kali ada user yang masuk ke sistem.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }

    private static function detectBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return '-';
        }

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'Chrome/') && ! str_contains($userAgent, 'Chromium') => 'Google Chrome',
            str_contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome') => 'Safari',
            default => 'Browser Lain',
        };
    }
}
