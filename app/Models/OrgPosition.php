<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgPosition extends Model
{
    use HasFactory;

    protected $table = 'org_positions';
    protected $primaryKey = 'position_id';
    public $incrementing = true;

    protected $fillable = [
        'org_id',
        'position_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the organization that owns the position.
     */
    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'org_id', 'org_id');
    }

    /**
     * Get the candidacy applications for this position.
     */
    public function candidacyApplications()
    {
        return $this->hasMany(CandidacyApplication::class, 'position_id', 'position_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'position_id';
    }
}
