<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Mortgage Calculator API",
 *     version="1.0.0",
 *     description="REST API for mortgage simulation using the French amortization method. Supports both fixed and variable interest rates, with complete simulation history and export functionality.",
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token in the format: **Bearer {token}**"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Mortgage Calculation",
 *     description="Calculate mortgage payments and save simulations"
 * )
 *
 * @OA\Tag(
 *     name="Simulation Management",
 *     description="Manage saved mortgage simulations"
 * )
 *
 * @OA\Tag(
 *     name="Export",
 *     description="Export amortization tables in CSV or Excel format"
 * )
 *
 * @OA\Tag(
 *     name="Health Check",
 *     description="API health monitoring"
 * )
 */
abstract class Controller
{
    //
}
