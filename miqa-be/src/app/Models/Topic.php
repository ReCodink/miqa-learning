<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Topic extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'about',
        'photo',
    ];

    public function getPhotoAttribute($value)
    {
        if (!$value) {
            return null;
        }

        return url(Storage::url($value));
    }


    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
