<?php

namespace Egarrido\NmsDevPanel\Http\Controllers;

use Egarrido\NmsDevPanel\Http\Requests\ReplaceDatabaseRequest;
use Egarrido\NmsDevPanel\Services\DatabaseDumpReplacer;
use Egarrido\NmsDevPanel\Services\PaymentConfigurationReconfigurator;
use Illuminate\Http\JsonResponse;

class ReplaceDatabaseController
{
    public function __invoke(
        ReplaceDatabaseRequest $request,
        DatabaseDumpReplacer $replacer,
        PaymentConfigurationReconfigurator $payments
    ): JsonResponse
    {
        $payments->ensureConfigured();
        $replacer->replace($request->file('dump'));

        return response()->json([
            'replaced' => true,
            'payments' => $payments->reconfigure(),
        ]);
    }
}
