<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodMoralRequest extends Model
{
    protected $fillable = [
        'full_name',
        'student_number',
        'course',
        'year_graduated',
        'contact_number',
        'email',
        'purpose',
        'status',
        'admin_notes',
    ];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending'          => 'Pending',
            'payment_verified' => 'Payment Verified',
            'ready_for_pickup' => 'Ready for Pickup',
            'released'         => 'Released',
            default            => ucfirst($this->status),
        };
    }
}
