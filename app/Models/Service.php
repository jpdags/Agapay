<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    // Fillable fields for mass assignment
    protected $fillable = [
        'name', 'description', 'rate', 'user_id'
    ];

    // Relationship: A service belongs to a user (provider)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
