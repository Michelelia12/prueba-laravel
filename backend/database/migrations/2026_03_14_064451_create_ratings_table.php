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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->integer('score')->min(1)->max(5);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->morphs('ratable'); // allows ratings on courses and instructors
            $table->unique(['user_id', 'ratable_id', 'ratable_type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
