<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Product;
use App\Models\Service;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'google_id',
        'google_calendar_token',
        'notifications_enabled',
        'suki_points',
        'phone',
        'address',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    // Orders where this user is the customer
    public function ordersAsCustomer()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    // Orders where this user is the provider
    public function ordersAsProvider()
    {
        return $this->hasMany(Order::class, 'provider_id');
    }

    // Calculate Suki Points from completed orders (1 point per ₱100)
    public function calculateSukiPoints()
    {
        $completedOrders = $this->ordersAsCustomer()
            ->where('status', 'completed')
            ->get();
        
        $totalAmount = $completedOrders->sum('total_amount');
        
        // 1 point per ₱100, rounded down
        return (int) floor($totalAmount / 100);
    }

    // Update suki_points based on completed orders
    public function updateSukiPoints()
    {
        $this->suki_points = $this->calculateSukiPoints();
        $this->save();
    }

    // Calculate average rating from completed orders as a provider
    public function getAverageRating()
    {
        $completedOrders = $this->ordersAsProvider()
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->get();
        
        if ($completedOrders->isEmpty()) {
            return null;
        }
        
        return round($completedOrders->avg('rating'), 2);
    }

    // Get total number of ratings
    public function getTotalRatings()
    {
        return $this->ordersAsProvider()
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->count();
    }
}
