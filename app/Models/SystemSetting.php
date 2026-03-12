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

    // ─── Which module owns each lookup key ───
    public const LOOKUP_MODULE_MAP = [
        'sports_equipment'           => 'sports',
        'organization_types'         => 'organizations',
        'default_org_positions'      => 'organizations',
        'event_statuses'             => 'organizations',
        'guidance_case_types'        => 'guidance',
        'guidance_appointment_types' => 'guidance',
        'violation_severities'       => 'discipline',
        'complaint_categories'       => 'discipline',
    ];

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

    /**
     * Return the lookup keys accessible to the given modules.
     * Full admins (who have 'students' module) get all keys.
     */
    public static function getAccessibleLookupKeys(array $accessibleModules): array
    {
        if (in_array('students', $accessibleModules, true)) {
            return array_keys(self::DEFAULTS);
        }

        $keys = [];
        foreach (self::LOOKUP_MODULE_MAP as $key => $module) {
            if (in_array($module, $accessibleModules, true)) {
                $keys[] = $key;
            }
        }
        return $keys;
    }
}
