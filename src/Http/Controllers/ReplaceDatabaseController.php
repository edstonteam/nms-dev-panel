<?php

namespace Edstonteam\NmsDevPanel\Http\Controllers;

use Edstonteam\NmsDevPanel\Http\Requests\ReplaceDatabaseRequest;
use Edstonteam\NmsDevPanel\Services\DatabaseDumpReplacer;
use Edstonteam\NmsDevPanel\Services\PaymentConfigurationReconfigurator;
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
