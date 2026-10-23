<?php

namespace Whilesmart\Notifications\ResponseFormatters;

use Illuminate\Http\JsonResponse;
use Whilesmart\Notifications\Contracts\ResponseFormatter;

class DefaultResponseFormatter implements ResponseFormatter
{
    public function format(array $payload, int $statusCode = 200): JsonResponse
    {
        return response()->json($payload, $statusCode);
    }
}
