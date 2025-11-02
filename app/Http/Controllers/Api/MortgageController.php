<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateMortgageRequest;
use App\Http\Resources\MortgageCalculationResource;
use App\Services\MortgageCalculatorService;
use Illuminate\Http\JsonResponse;

class MortgageController extends Controller
{
    public function __construct(
        private MortgageCalculatorService $calculator
    ) {
    }
    public function calculate(CalculateMortgageRequest $request): MortgageCalculationResource
    {
        $validated = $request->validated();

        $getCalculatedData = $this->calculator->getCalculatedData($validated);

        return new MortgageCalculationResource($getCalculatedData);
    }

    // Health check
    // Nota: Laravel já fornece /up nativo. Este endpoint custom serve para:
    // - Consistência com namespace /api/*
    // - Expansão futura com checks de dependências externas
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'mortgage-calculator-api',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
