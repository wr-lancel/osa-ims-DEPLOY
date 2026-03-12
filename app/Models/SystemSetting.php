<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    // ─── Default values for each configurable list ───
    public const DEFAULTS = [
        'organization_types' => ['Academic', 'Cultural', 'Governance', 'Special Interest'],
        'complaint_categories' => ['Academic Integrity', 'Campus Conduct', 'Prohibited Activities', 'Other'],
        'guidance_case_types' => ['counseling', 'consultation', 'referral'],
        'guidance_appointment_types' => ['counseling', 'consultation', 'referral', 'other'],
        'event_statuses' => ['Planning', 'Upcoming', 'Completed'],
        'violation_severities' => ['Minor', 'Moderate', 'Major'],
        'default_org_positions' => ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor', 'PIO', 'Business Manager', 'Sergeant-at-Arms'],
        'sports_equipment' => ['Basketballs', 'Volleyballs', 'Badminton Sets', 'Table Tennis Paddles', 'Soccer Balls', 'Chess Sets', 'Tennis Rackets', 'Jump Ropes', 'Yoga Mats'],
    ];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, $default = null): ?string
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get a JSON list setting, with fallback to hardcoded defaults.
     */
    public static function getList(string $key): array
    {
        $value = static::getValue($key);
        if ($value) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return self::DEFAULTS[$key] ?? [];
    }

    /**
     * Set a JSON list setting.
     */
    public static function setList(string $key, array $values): void
    {
        static::setValue($key, json_encode(array_values($values)));
    }

    /**
     * Check if candidacy submissions are globally open.
     */
    public static function isCandidacyOpen(): bool
    {
        return (bool) static::getValue('candidacy_submissions_open', '0');
    }
}
