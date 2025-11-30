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
        Schema::create('entrepreneurships', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // business name
            $table->string('category'); // e.g., Food, Crafts
            $table->string('contact');
            $table->text('description');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // assuming entrepreneurship belongs to a provider (user)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrepreneuships');
    }
};
