<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'ebay_category_id',
        'name',
        'specifics',
    ];

    protected $casts = [
        'specifics' => 'array',
    ];
} 