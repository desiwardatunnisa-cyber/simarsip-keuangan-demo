<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class ActivityTimelineWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.activity-timeline-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 5;

    public function getLogs()
    {
        $user = auth()->user();
        $divisi = $this->filters['divisi'] ?? null;

        $query = AuditLog::query()->visibleTo($user)->with('user');

        if ($divisi && $user->isAdminIT()) {
            $query->whereHas('user', fn ($q) => $q->where('departemen', $divisi));
        }

        return $query->latest()->limit(8)->get();
    }

    public function meta(string $aksi): array
    {
        return match ($aksi) {
            'created' => ['label' => 'menambahkan', 'icon' => 'heroicon-o-plus-circle', 'color' => 'success'],
            'updated' => ['label' => 'mengubah', 'icon' => 'heroicon-o-pencil-square', 'color' => 'warning'],
            'deleted' => ['label' => 'menghapus', 'icon' => 'heroicon-o-trash', 'color' => 'danger'],
            default => ['label' => $aksi, 'icon' => 'heroicon-o-bolt', 'color' => 'gray'],
        };
    }
}
