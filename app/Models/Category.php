<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];



    public function courses(): MorphToMany
    {
        return $this->morphedByMany(Course::class, 'categorizable', 'categorizables')
                    ->withTimestamps();
    }

    public function lessons(): MorphToMany
    {
        return $this->morphedByMany(Lesson::class, 'categorizable', 'categorizables')
                    ->withTimestamps();
    }

    public function assignments(): MorphToMany
    {
        return $this->morphedByMany(Assignment::class, 'categorizable', 'categorizables')
                    ->withTimestamps();
    }
}
