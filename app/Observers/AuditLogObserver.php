<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    public function created(Model $model): void
    {
        AuditLog::catat('created', class_basename($model), $model->getKey(), $this->label($model));
    }

    public function updated(Model $model): void
    {
        AuditLog::catat('updated', class_basename($model), $model->getKey(), $this->label($model));
    }

    public function deleted(Model $model): void
    {
        AuditLog::catat('deleted', class_basename($model), $model->getKey(), $this->label($model));
    }

    private function label(Model $model): ?string
    {
        return $model->judul_dokumen ?? $model->nama_kategori ?? $model->name ?? null;
    }
}