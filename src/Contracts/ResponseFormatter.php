<?php

namespace Whilesmart\Notifications\Contracts;

use Illuminate\Http\JsonResponse;

interface ResponseFormatter
{
    public function format(array $payload, int $statusCode = 200): JsonResponse;
}
