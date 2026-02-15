<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait HasActivityLog
{
    protected function logActivity(string $action, string $modelType, string $modelId, ?string $description = null): void
    {
        try {
            $user = Auth::user();
            $userId = $user?->id;

            ActivityLog::registrar(
                $action,
                $modelType,
                $modelId,
                $description ?? '',
                null,
                null,
                $userId ?? null
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al registrar activity log: '.$e->getMessage());
        }
    }
}
