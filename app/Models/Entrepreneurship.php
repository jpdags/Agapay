<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrepreneurship extends Model
{
    use HasFactory;

    // Specify table name since filename doesn't match class name
    protected $table = 'entrepreneurships';

    // Fillable fields for mass assignment
    protected $fillable = [
        'name', 'category', 'contact', 'description', 'user_id'
    ];

    // Relationship: An entrepreneurship belongs to a user (provider)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
