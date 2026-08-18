<?php

namespace App\Services\Backups;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class BackupAuditService
{
    public function record(string $event, ?Model $auditable = null, array $metadata = [], ?int $userId = null): void
    {
        $request = request();

        AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request instanceof Request ? $request->ip() : null,
            'user_agent' => $request instanceof Request ? $request->userAgent() : null,
            'metadata' => $metadata,
        ]);
    }
}
