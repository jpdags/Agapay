<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable(); // e.g., Petron, Shell, PryceGas
            $table->decimal('price', 8, 2);
            $table->decimal('delivery_fee', 8, 2)->nullable();
            $table->float('rating', 2, 1)->default(0);
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // assuming products belong to a provider (user)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
