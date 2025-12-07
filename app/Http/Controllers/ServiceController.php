<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Service accessed successfully',
            'data' => [
                'timestamp' => now(),
                'status' => 'active',
            ]
        ]);
    }
}
