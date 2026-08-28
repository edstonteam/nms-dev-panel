<?php

namespace Egarrido\NmsDevPanel\Http\Controllers;

use Egarrido\NmsDevPanel\Services\PaymentConfigurationReconfigurator;
use Illuminate\Http\JsonResponse;

class ReconfigurePaymentsController
{
    public function __invoke(PaymentConfigurationReconfigurator $reconfigurator): JsonResponse
    {
        return response()->json($reconfigurator->reconfigure());
    }
}
