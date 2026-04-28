<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_category_preferences', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'category_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_category_preferences');
    }
};
