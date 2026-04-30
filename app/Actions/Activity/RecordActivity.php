<?php

namespace App\Actions\Activity;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;

class RecordActivity
{
    public function handle(
        Workspace $workspace,
        ?User $user,
        string $action,
        string $description,
                  $subject = null
    ): void {
        ActivityLog::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
        ]);
    }
}
