<?php

namespace App\Models;

use App\Core\Model;

class ProjectIntake extends Model
{
    protected static string $table = 'project_intakes';

    public static function forClient(int|string $clientId): array
    {
        return static::query()->where('client_id', $clientId)->orderBy('id', 'desc')->get();
    }

    /** Question set for a service-line slug, or null if it has no questionnaire. */
    public static function questionsFor(?string $category): ?array
    {
        if (! $category) {
            return null;
        }

        return config('intake.' . $category) ?: null;
    }
}
