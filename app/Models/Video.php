<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Video extends Model
{
    use HasFactory;

    protected $fillable = [
    'youtube_id',
    'keyword',
    'title',
    'description',
    'language',
    'url',
    'published_at',
   ];
}
