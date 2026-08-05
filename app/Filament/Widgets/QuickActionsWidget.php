<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\BackupDatabase;
use App\Filament\Pages\LaporanDokumen;
use App\Filament\Pages\MonitoringStaff;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\DocumentResource;
use App\Filament\Resources\UserResource;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public function getActions(): array
    {
        $user = auth()->user();

        if ($user->isAdminIT()) {
            return [
                ['label' => 'Upload Dokumen', 'icon' => 'heroicon-o-document-arrow-up', 'color' => 'primary', 'url' => DocumentResource::getUrl('create')],
                ['label' => 'Kelola User', 'icon' => 'heroicon-o-users', 'color' => 'success', 'url' => UserResource::getUrl('index')],
                ['label' => 'Kategori Dokumen', 'icon' => 'heroicon-o-tag', 'color' => 'warning', 'url' => CategoryResource::getUrl('index')],
                ['label' => 'Laporan', 'icon' => 'heroicon-o-chart-bar', 'color' => 'info', 'url' => LaporanDokumen::getUrl()],
                ['label' => 'Backup Database', 'icon' => 'heroicon-o-circle-stack', 'color' => 'danger', 'url' => BackupDatabase::getUrl()],
            ];
        }

        if ($user->isKabag() || $user->isAdminBagian()) {
            return [
                ['label' => 'Upload Dokumen', 'icon' => 'heroicon-o-document-arrow-up', 'color' => 'primary', 'url' => DocumentResource::getUrl('create')],
                ['label' => 'Kategori Dokumen', 'icon' => 'heroicon-o-tag', 'color' => 'warning', 'url' => CategoryResource::getUrl('index')],
                ['label' => 'Monitoring Staff', 'icon' => 'heroicon-o-eye', 'color' => 'info', 'url' => MonitoringStaff::getUrl()],
                ['label' => 'Laporan', 'icon' => 'heroicon-o-chart-bar', 'color' => 'success', 'url' => LaporanDokumen::getUrl()],
            ];
        }

        return [
            ['label' => 'Upload Dokumen', 'icon' => 'heroicon-o-document-arrow-up', 'color' => 'primary', 'url' => DocumentResource::getUrl('create')],
            ['label' => 'Arsip Saya', 'icon' => 'heroicon-o-archive-box', 'color' => 'success', 'url' => DocumentResource::getUrl('index')],
            ['label' => 'Laporan Saya', 'icon' => 'heroicon-o-chart-bar', 'color' => 'info', 'url' => LaporanDokumen::getUrl()],
        ];
    }
}
