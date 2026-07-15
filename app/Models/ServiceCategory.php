<?php

namespace App\Models;

use App\Core\Model;

/**
 * The service lines (Web Design, SEO, SMM, Hosting). Stored in a table rather
 * than hard-coded so new lines are added in-app with zero code change — the
 * "easily add more later" requirement.
 */
class ServiceCategory extends Model
{
    protected static string $table = 'service_categories';

    public static function ordered(): array
    {
        return static::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    public static function options(): array
    {
        $options = [];
        foreach (static::ordered() as $category) {
            $options[$category['id']] = $category['name'];
        }

        return $options;
    }
}
