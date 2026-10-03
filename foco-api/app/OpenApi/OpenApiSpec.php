<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Foco Multimídia API',
    description: 'API REST desenvolvida para o teste técnico da Foco Multimídia.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Servidor local de desenvolvimento'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum Token'
)]
class OpenApiSpec
{
}
