<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorizables', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->morphs('categorizable'); // adds categorizable_id + categorizable_type + index
            $table->timestamps();

            // Prevent assigning the same category to the same content item twice
            $table->unique(['category_id', 'categorizable_id', 'categorizable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorizables');
    }
};
