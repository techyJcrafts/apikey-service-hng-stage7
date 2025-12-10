<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(title: "Laravel API Auth", version: "1.0.0")]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\SecurityScheme(
    securityScheme: "apiKey",
    type: "apiKey",
    in: "header",
    name: "x-api-key",
    description: "API Key Authentication"
)]
abstract class Controller
{
    //
}
