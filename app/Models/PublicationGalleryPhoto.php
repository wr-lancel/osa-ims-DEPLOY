<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicationGalleryPhoto extends Model
{
    protected $primaryKey = 'photo_id';

    protected $fillable = [
        'gallery_id',
        'image_path',
        'caption',
        'sort_order',
    ];

    public function gallery()
    {
        return $this->belongsTo(PublicationGallery::class, 'gallery_id', 'gallery_id');
    }
}
