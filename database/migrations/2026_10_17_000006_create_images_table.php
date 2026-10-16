<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable'); // adds imageable_id + imageable_type + index
            $table->string('path');
            $table->timestamps();

            // Enforce one-to-one: each content item can have only one image
            $table->unique(['imageable_id', 'imageable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
