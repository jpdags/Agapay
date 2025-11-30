<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'provider_id',
        'offering_type',
        'offering_id',
        'status',
        'notes',
        'scheduled_date',
        'scheduled_time',
        'total_amount',
        'quantity',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    // Cache for the offering
    protected $cachedOffering = null;

    // Get the offering (product, service, or business) - manual resolution
    public function getOfferingAttribute()
    {
        if ($this->cachedOffering !== null) {
            return $this->cachedOffering;
        }

        if (!$this->offering_type || !$this->offering_id) {
            return null;
        }

        $typeMap = [
            'product' => Product::class,
            'service' => Service::class,
            'business' => Entrepreneurship::class,
        ];

        $modelClass = $typeMap[$this->offering_type] ?? null;
        
        if (!$modelClass) {
            return null;
        }

        $this->cachedOffering = $modelClass::find($this->offering_id);
        return $this->cachedOffering;
    }

    // Method to manually load offering (for eager loading scenarios)
    public function loadOffering()
    {
        // This will trigger the accessor and cache it
        $this->offering;
    }

    // Helper method to get the offering name
    public function getOfferingNameAttribute()
    {
        if ($this->offering) {
            return $this->offering->name ?? 'Unknown';
        }
        return 'Unknown';
    }
}
