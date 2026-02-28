<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplineViolationType extends Model
{
    protected $fillable = [
        'name',
        'severity',
        'description',
        'default_sanction',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Scope: order by sort_order ascending.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Scope: filter by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get all types grouped by severity for the frontend.
     */
    public static function getAllGrouped(): array
    {
        return static::ordered()->get()
            ->groupBy('severity')
            ->map(fn($group) => $group->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'severity' => $t->severity,
                'description' => $t->description,
                'default_sanction' => $t->default_sanction,
                'sort_order' => $t->sort_order,
            ])->values())
            ->toArray();
    }

    /**
     * Get flat list for frontend dropdowns.
     */
    public static function getAllForDropdown(): array
    {
        return static::ordered()->get()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'severity' => $t->severity,
            'default_sanction' => $t->default_sanction,
        ])->toArray();
    }

    /**
     * Get valid type names (for validation).
     */
    public static function getTypeNames(): array
    {
        return static::pluck('name')->unique()->toArray();
    }

    /**
     * Get the next sort order for a given severity.
     */
    public static function nextSortOrder(string $severity): int
    {
        return (static::where('severity', $severity)->max('sort_order') ?? 0) + 1;
    }
}
