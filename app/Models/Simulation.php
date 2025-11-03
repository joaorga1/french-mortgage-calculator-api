<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Simulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'loan_amount',
        'duration_months',
        'rate_type',
        'annual_rate',
        'index_rate',
        'spread',
        'monthly_payment',
        'total_amount',
        'total_interest',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'annual_rate' => 'decimal:2',
        'index_rate' => 'decimal:2',
        'spread' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_interest' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
