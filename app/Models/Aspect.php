<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aspect extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'values',
        'required',
        'data_type',
        'mode',
        'usage',
    ];

    protected $casts = [
        'values' => 'array',
        'required' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
} 