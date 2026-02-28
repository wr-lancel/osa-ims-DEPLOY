<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentOrganization extends Model
{
    use HasFactory;

    protected $table = 'student_org';
    protected $primaryKey = 'org_id';
    public $incrementing = true;

    protected $fillable = [
        'org_name',
        'org_code',
        'description',
        'type',
        'status',
        'candidacy_open',
        'adviser_name',
        'mission',
        'mission_file',
        'vision',
        'vision_file',
        'goals',
        'goals_file',
        'constitution_bylaws',
        'constitution_bylaws_file',
    ];

    protected $casts = [
        'candidacy_open' => 'boolean',
    ];

    /**
     * Get the officers for this organization.
     */
    public function officers()
    {
        return $this->hasMany(OrgOfficer::class, 'org_id', 'org_id')
            ->where('status', 'active');
    }

    /**
     * Get the members for this organization.
     */
    public function members()
    {
        return $this->hasMany(OrgMember::class, 'org_id', 'org_id')
            ->where('status', 'active');
    }

    /**
     * Get the advisers for this organization.
     */
    public function advisers()
    {
        return $this->hasMany(OrgAdviser::class, 'org_id', 'org_id')
            ->where('status', 'active');
    }

    /**
     * Get the events for this organization.
     */
    public function events()
    {
        return $this->hasMany(Event::class, 'org_id', 'org_id');
    }

    /**
     * Get the meetings for this organization.
     */
    public function meetings()
    {
        return $this->hasMany(OrgMeeting::class, 'org_id', 'org_id');
    }

    /**
     * Get the positions for this organization.
     */
    public function positions()
    {
        return $this->hasMany(OrgPosition::class, 'org_id', 'org_id');
    }

    /**
     * Get the candidacy applications for this organization.
     */
    public function candidacyApplications()
    {
        return $this->hasMany(CandidacyApplication::class, 'org_id', 'org_id');
    }

    /**
     * Get the president (officer with position 'President').
     */
    public function president()
    {
        return $this->hasOne(OrgOfficer::class, 'org_id', 'org_id')
            ->where('position', 'President')
            ->where('status', 'active');
    }

    /**
     * Get the current adviser.
     */
    public function currentAdviser()
    {
        return $this->hasOne(OrgAdviser::class, 'org_id', 'org_id')
            ->where('status', 'active')
            ->latest();
    }

    /**
     * Get members count.
     */
    public function getMembersCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Get president name.
     */
    public function getPresidentNameAttribute(): ?string
    {
        $president = $this->president;
        if ($president && $president->student) {
            return $president->student->full_name;
        }
        return null;
    }

    /**
     * Get adviser name - uses the adviser_name field first, falls back to employee relationship.
     */
    public function getAdviserDisplayNameAttribute(): ?string
    {
        // First check the direct adviser_name field
        if (!empty($this->attributes['adviser_name'])) {
            return $this->attributes['adviser_name'];
        }

        // Fall back to employee-based adviser
        $adviser = $this->currentAdviser;
        if ($adviser && $adviser->employee) {
            return $adviser->employee->full_name;
        }
        return null;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'org_id';
    }
}

