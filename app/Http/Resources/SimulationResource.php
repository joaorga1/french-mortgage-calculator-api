<?php

namespace App\Http\Resources;

use App\Models\Simulation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Simulation
 */
class SimulationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'loan_amount' => $this->loan_amount,
            'duration_months' => $this->duration_months,
            'rate_type' => $this->rate_type,
            'annual_rate' => $this->annual_rate,
            'index_rate' => $this->when($this->rate_type === 'variable', $this->index_rate),
            'spread' => $this->when($this->rate_type === 'variable', $this->spread),
            'monthly_payment' => $this->monthly_payment,
            'total_amount' => $this->total_amount,
            'total_interest' => $this->total_interest,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
