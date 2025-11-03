<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateMortgageRequest;
use App\Http\Resources\SimulationResource;
use App\Models\Simulation;
use App\Services\AmortizationTableService;
use App\Services\SimulationExportService;
use App\Services\SimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MortgageController extends Controller
{
    public function __construct(
        private SimulationService $simulationService,
        private SimulationExportService $exportService,
        private AmortizationTableService $amortizationService
    ) {
    }

    public function calculate(CalculateMortgageRequest $request): JsonResponse
    {
        try {
            $simulation = $this->simulationService->create(
                $request->validated(),
                $request->user()->id
            );

            return response()->json([
                'data' => [
                    'id' => $simulation->id,
                    'monthly_payment' => $simulation->monthly_payment,
                    'loan_amount' => $simulation->loan_amount,
                    'duration_months' => $simulation->duration_months,
                    'annual_rate' => $simulation->annual_rate,
                    'total_amount' => $simulation->total_amount,
                    'total_interest' => $simulation->total_interest,
                    'method' => 'french_amortization',
                    'currency' => 'EUR',
                    'metadata' => [
                        'calculation_date' => $simulation->created_at->toIso8601String(),
                        'formula' => 'M = P * [i(1 + i)^n] / [(1 + i)^n - 1]',
                    ],
                ],
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Failed to create simulation', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Failed to create simulation',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $simulations = Simulation::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return SimulationResource::collection($simulations);
    }

    public function show(Simulation $simulation, Request $request): JsonResponse
    {
        if ($simulation->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $resource = new SimulationResource($simulation);
        $data = $resource->toArray($request);

        // Add amortization table
        $data['amortization_table'] = $this->amortizationService->generate($simulation);

        return response()->json($data);
    }

    public function export(Simulation $simulation, Request $request): mixed
    {
        if ($simulation->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $format = $request->query('format', 'csv');

        if (! in_array($format, ['csv', 'excel'])) {
            return response()->json(['error' => 'Invalid format. Use csv or excel'], 400);
        }

        if ($format === 'csv') {
            $content = $this->exportService->toCsv($simulation);

            return response($content, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', "attachment; filename=simulation_{$simulation->id}.csv");
        }

        return $this->exportService->toExcel($simulation);
    }

    // Health check
    // Note: Laravel already provides /up natively. This custom endpoint serves for:
    // - Consistency with /api/* namespace
    // - Future expansion with checks of external dependencies
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'mortgage-calculator-api',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
