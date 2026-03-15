<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 */
class Instructor extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'bio', 'avatar'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'ratable');
    }

    public function averageRating(): float
    {
        $avg = $this->ratings()::avg('score');

        return is_numeric($avg) ? (float) $avg : 0.0;
    }
}
