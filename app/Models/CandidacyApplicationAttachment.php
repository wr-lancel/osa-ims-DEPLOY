<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidacyApplicationAttachment extends Model
{
    use HasFactory;

    protected $table = 'candidacy_application_attachments';
    protected $primaryKey = 'attachment_id';
    public $incrementing = true;

    protected $fillable = [
        'application_id',
        'file_name',
        'file_path',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /**
     * Get the candidacy application.
     */
    public function candidacyApplication()
    {
        return $this->belongsTo(CandidacyApplication::class, 'application_id', 'application_id');
    }
}
