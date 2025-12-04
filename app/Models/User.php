<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Product;
use App\Models\Service;
use App\Models\Entrepreneurship;

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

    public function businesses()
    {
        return $this->hasMany(Entrepreneurship::class);
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
}
