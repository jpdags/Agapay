<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
            
            // Polymorphic relationship for the offering (product or service)
            $table->string('offering_type'); // 'product' or 'service'
            $table->unsignedBigInteger('offering_id'); // ID of the product or service
            
            $table->string('status')->default('pending'); // pending, accepted, declined, completed
            $table->text('notes')->nullable(); // Customer notes/requirements
            $table->date('scheduled_date')->nullable(); // When the service/product should be delivered
            $table->time('scheduled_time')->nullable(); // Time for scheduled delivery/service
            
            $table->decimal('total_amount', 10, 2)->nullable(); // Total price including delivery fee if applicable
            $table->integer('quantity')->default(1); // For products
            
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['customer_id', 'status']);
            $table->index(['provider_id', 'status']);
            $table->index(['offering_type', 'offering_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
