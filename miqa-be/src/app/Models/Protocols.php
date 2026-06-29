<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Protocols extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'name',
        'description'
    ];

    protected static function booted()
    {
        static::creating(function ($protocol) {

            $latestProtocol = static::latest('created_at')->first();

            if ($latestProtocol && $latestProtocol->code) {
                $latestNumber = (int) str_replace('PRT-', '', $latestProtocol->code);
                $nextNumber = $latestNumber + 1;
            } else {
                $nextNumber = 1; // Mulai dari 1 jika belum ada record sama sekali
            }

            $protocol->code = 'PRT-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    public function classRooms(): HasMany
    {
        return $this->hasMany(ClassRoom::class, 'protocol_id');
    }
}
