<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorySearchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'suggestions',
    ];

    protected $casts = [
        'suggestions' => 'array',
    ];
}