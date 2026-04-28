<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('image_url')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('author')->default('Admin');
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
