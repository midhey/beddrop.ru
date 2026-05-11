<?php

namespace App\Services\Admin;

use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminActionLogger
{
    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @param array<string, mixed>|null $metadata
     */
    public function log(
        User $admin,
        string $action,
        Model|string $target,
        int|string|null $targetId = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null,
    ): AdminActionLog {
        $targetType = is_string($target) ? $target : $target::class;
        $resolvedTargetId = is_string($target) ? $targetId : $target->getKey();

        return AdminActionLog::create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $resolvedTargetId === null ? null : (string) $resolvedTargetId,
            'before_json' => $before,
            'after_json' => $after,
            'metadata_json' => $metadata,
        ]);
    }
}
