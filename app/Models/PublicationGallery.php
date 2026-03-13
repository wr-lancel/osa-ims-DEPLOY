<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PublicationGallery extends Model
{
    protected $primaryKey = 'gallery_id';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'cover_image',
        'status',
        'rejection_reason',
        'author_id',
        'reviewed_by',
        'published_at',
        'reviewed_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    public function photos()
    {
        return $this->hasMany(PublicationGalleryPhoto::class, 'gallery_id', 'gallery_id')
            ->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
