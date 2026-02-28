<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplineWorkflowStep extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_terminal',
    ];

    protected $casts = [
        'is_terminal' => 'boolean',
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
     * Scope: only terminal steps.
     */
    public function scopeTerminal($query)
    {
        return $query->where('is_terminal', true);
    }

    /**
     * Get ordered list of step names (for validation rules).
     */
    public static function getStepNames(): array
    {
        return static::ordered()->pluck('name')->toArray();
    }

    /**
     * Get steps formatted for the StatusProgressBar Vue component.
     */
    public static function getStepsForProgressBar(): array
    {
        return static::ordered()->get()->map(fn($step) => [
            'value' => $step->name,
            'label' => $step->name,
        ])->toArray();
    }

    /**
     * Get terminal status names (for the StatusProgressBar).
     */
    public static function getTerminalNames(): array
    {
        return static::terminal()->pluck('name')->toArray();
    }

    /**
     * Get the next available sort order value.
     */
    public static function nextSortOrder(): int
    {
        return (static::max('sort_order') ?? 0) + 1;
    }
}
