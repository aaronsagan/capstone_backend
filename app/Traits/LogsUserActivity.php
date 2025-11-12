<?php

namespace App\Traits;

use App\Models\UserActivityLog;

trait LogsUserActivity
{
    /**
     * Log user activity
     */
    protected function logActivity(
        string $actionType,
        ?string $description = null,
        ?string $targetType = null,
        ?int $targetId = null,
        ?array $details = null,
        ?int $userId = null
    ): void {
        $userId = $userId ?? auth()->id();
        
        if (!$userId) {
            return;
        }

        UserActivityLog::log(
            $userId,
            $actionType,
            $description,
            $targetType,
            $targetId,
            $details
        );
    }
}
