<?php

namespace Egarrido\NmsDevPanel\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ClearCookiesController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['cleared' => true]);
    }
}
