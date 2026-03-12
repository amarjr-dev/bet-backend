<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'driver',
        'is_active',
        'priority',
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'priority'    => 'integer',
            'credentials' => 'encrypted',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('priority');
    }
}
