<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Fillable fields for mass assignment
    protected $fillable = [
        'name', 'brand', 'price', 'delivery_fee', 'rating', 'user_id'
    ];

    // Relationship: A product belongs to a user (provider)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

