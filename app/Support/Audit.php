<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Audit
{
    public static function log(string $action, ?Model $model = null, array $oldValues = [], array $newValues = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $model ? $model::class : null,
            'auditable_id' => $model?->getKey(),
            'old_values' => self::sanitize($oldValues) ?: null,
            'new_values' => self::sanitize($newValues) ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    private static function sanitize(array $values): array
    {
        unset($values['password'], $values['remember_token']);

        return $values;
    }
}
