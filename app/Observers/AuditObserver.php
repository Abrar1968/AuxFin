<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->write('created', $model, null, $this->sanitize($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        $original = Arr::only($model->getOriginal(), array_keys($changes));

        $this->write('updated', $model, $this->sanitize($original), $this->sanitize($changes));
    }

    public function deleted(Model $model): void
    {
        $this->write('deleted', $model, $this->sanitize($model->getOriginal()), null);
    }

    private function write(string $action, Model $model, ?array $oldValues, ?array $newValues): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        AuditLog::query()->create([
            'user_id' => request()?->user()?->id,
            'action' => $action,
            'model_type' => class_basename($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    private function sanitize(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        return Arr::except($values, [
            'passkey',
            'passkey_plain',
            'remember_token',
            'deleted_at',
        ]);
    }
}
