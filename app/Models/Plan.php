<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'trial_period_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trial_period_days' => 'integer',
            'is_active'         => 'boolean',
        ];
    }


   



    public function prices(): HasMany
    {
        return $this->hasMany(PlanPrice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }



    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    public function hasTrial(): bool
    {
        return $this->trial_period_days > 0;
    }
}
