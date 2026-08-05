<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.welcome-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -10;

    public function roleLabel(): string
    {
        $user = auth()->user();

        if ($user->isAdminIT()) {
            return 'Super Admin';
        }

        if ($user->isKabag()) {
            return 'Kepala Bagian';
        }

        $departemenLabel = match ($user->departemen) {
            'keuangan' => 'Keuangan',
            default => 'Akuntansi',
        };

        if ($user->isAdminBagian()) {
            return 'Admin Bagian ' . $departemenLabel;
        }

        return 'Staff ' . $departemenLabel;
    }
}