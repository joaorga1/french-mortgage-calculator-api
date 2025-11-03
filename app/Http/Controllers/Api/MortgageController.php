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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

class MortgageController extends Controller
{
    public function __construct(
        private SimulationService $simulationService,
        private SimulationExportService $exportService,
        private AmortizationTableService $amortizationService
    ) {
    }

    /**
     * @OA\Post(
     *     path="/api/mortgage/calculate",
     *     tags={"Mortgage Calculation"},
     *     summary="Calculate mortgage payment",
     *     description="Calculate monthly mortgage payment using French amortization method. Supports both fixed and variable rates. The simulation is automatically saved to user's history.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Mortgage calculation parameters",
     *         @OA\JsonContent(
     *             required={"loan_amount", "duration_months", "type"},
     *             @OA\Property(property="loan_amount", type="number", example=200000, description="Loan amount in EUR (5,000 - 10,000,000)"),
     *             @OA\Property(property="duration_months", type="integer", example=360, description="Loan duration in months (60-480, i.e., 5-40 years)"),
     *             @OA\Property(property="type", type="string", enum={"fixed", "variable"}, example="fixed", description="Interest rate type"),
     *             @OA\Property(property="rate", type="number", example=3.5, description="Annual interest rate (required for fixed rate, 0-100%)"),
     *             @OA\Property(property="index_rate", type="number", example=2.5, description="Index rate like Euribor (required for variable rate, 0-100%)"),
     *             @OA\Property(property="spread", type="number", example=1.3, description="Bank spread (required for variable rate, 0-100%)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Calculation successful and simulation saved",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1, description="Simulation ID"),
     *                 @OA\Property(property="monthly_payment", type="string", example="898.09", description="Monthly payment amount"),
     *                 @OA\Property(property="loan_amount", type="string", example="200000.00", description="Original loan amount"),
     *                 @OA\Property(property="duration_months", type="integer", example=360),
     *                 @OA\Property(property="annual_rate", type="string", example="3.50", description="Applied annual rate"),
     *                 @OA\Property(property="total_amount", type="string", example="323312.40", description="Total amount to be paid"),
     *                 @OA\Property(property="total_interest", type="string", example="123312.40", description="Total interest paid"),
     *                 @OA\Property(property="method", type="string", example="french_amortization"),
     *                 @OA\Property(property="currency", type="string", example="EUR"),
     *                 @OA\Property(
     *                     property="metadata",
     *                     type="object",
     *                     @OA\Property(property="calculation_date", type="string", format="date-time", example="2025-11-03T12:00:00+00:00"),
     *                     @OA\Property(property="formula", type="string", example="M = P * [i(1 + i)^n] / [(1 + i)^n - 1]")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="O valor do empréstimo tem de ser pelo menos 5000€"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="loan_amount", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many requests",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Too Many Attempts.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to create simulation"),
     *             @OA\Property(property="error", type="string", example="An error occurred")
     *         )
     *     )
     * )
     */
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


    /**
     * @OA\Get(
     *     path="/api/simulations",
     *     tags={"Simulation Management"},
     *     summary="List user simulations",
     *     description="Retrieve a paginated list of all simulations for the authenticated user, ordered by most recent first.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Simulations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="loan_amount", type="string", example="200000.00"),
     *                     @OA\Property(property="duration_months", type="integer", example=360),
     *                     @OA\Property(property="rate_type", type="string", example="fixed"),
     *                     @OA\Property(property="annual_rate", type="string", example="3.50"),
     *                     @OA\Property(property="monthly_payment", type="string", example="898.09"),
     *                     @OA\Property(property="total_amount", type="string", example="323312.40"),
     *                     @OA\Property(property="total_interest", type="string", example="123312.40"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-03T10:30:00+00:00")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://localhost/api/simulations?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://localhost/api/simulations?page=2"),
     *                 @OA\Property(property="prev", type="string", nullable=true, example=null),
     *                 @OA\Property(property="next", type="string", nullable=true, example="http://localhost/api/simulations?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=2),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $simulations = Simulation::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return SimulationResource::collection($simulations);
    }

    /**
     * @OA\Get(
     *     path="/api/simulations/{id}",
     *     tags={"Simulation Management"},
     *     summary="View simulation details",
     *     description="Get detailed information about a specific simulation, including the complete month-by-month amortization table.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Simulation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Simulation details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="loan_amount", type="string", example="200000.00"),
     *             @OA\Property(property="duration_months", type="integer", example=360),
     *             @OA\Property(property="rate_type", type="string", example="fixed"),
     *             @OA\Property(property="annual_rate", type="string", example="3.50"),
     *             @OA\Property(property="monthly_payment", type="string", example="898.09"),
     *             @OA\Property(property="total_amount", type="string", example="323312.40"),
     *             @OA\Property(property="total_interest", type="string", example="123312.40"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="amortization_table",
     *                 type="array",
     *                 description="Complete month-by-month payment breakdown",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="month", type="integer", example=1),
     *                     @OA\Property(property="payment", type="number", example=898.09),
     *                     @OA\Property(property="principal", type="number", example=314.76, description="Amount applied to principal"),
     *                     @OA\Property(property="interest", type="number", example=583.33, description="Interest portion"),
     *                     @OA\Property(property="balance", type="number", example=199685.24, description="Remaining balance")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Cannot access other user's simulation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Simulation not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Simulation] 999")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/simulations/{id}/export",
     *     tags={"Export"},
     *     summary="Export amortization table",
     *     description="Download the complete amortization table in CSV or Excel format. The file includes all monthly payments with principal, interest, and remaining balance.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Simulation ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         description="Export format",
     *         required=false,
     *         @OA\Schema(type="string", enum={"csv", "excel"}, default="csv")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         ),
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid format",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Invalid format. Use csv or excel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Forbidden")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Simulation not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No query results for model [App\\Models\\Simulation] 999")
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Get(
     *     path="/api/health",
     *     tags={"Health Check"},
     *     summary="API health check",
     *     description="Check if the API and its dependencies (database) are running and responsive. Returns detailed status for monitoring and alerting systems.",
     *     @OA\Response(
     *         response=200,
     *         description="API and all dependencies are healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="service", type="string", example="mortgage-calculator-api"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-11-03T12:00:00+00:00"),
     *             @OA\Property(
     *                 property="checks",
     *                 type="object",
     *                 @OA\Property(
     *                     property="database",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="ok"),
     *                     @OA\Property(property="connection", type="string", example="mysql")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Service unavailable - one or more dependencies are down",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="service", type="string", example="mortgage-calculator-api"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(
     *                 property="checks",
     *                 type="object",
     *                 @OA\Property(
     *                     property="database",
     *                     type="object",
     *                     @OA\Property(property="status", type="string", example="error"),
     *                     @OA\Property(property="error", type="string", example="Connection refused")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function health(): JsonResponse
    {
        $checks = [];
        $overallStatus = 'ok';
        $statusCode = 200;

        // Check database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'status' => 'ok',
                'connection' => config('database.default'),
            ];
        } catch (\Throwable $e) {
            $overallStatus = 'error';
            $statusCode = 503;
            $checks['database'] = [
                'status' => 'error',
                'error' => config('app.debug') ? $e->getMessage() : 'Database connection failed',
            ];

            Log::error('Health check failed - database connection', [
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => $overallStatus,
            'service' => 'mortgage-calculator-api',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $statusCode);
    }
}
