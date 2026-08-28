<?php

namespace Edstonteam\NmsDevPanel\Http\Controllers;

use Edstonteam\NmsDevPanel\Services\PaymentConfigurationReconfigurator;
use Illuminate\Http\JsonResponse;

class ReconfigurePaymentsController
{
    public function __invoke(PaymentConfigurationReconfigurator $reconfigurator): JsonResponse
    {
        return response()->json($reconfigurator->reconfigure());
    }
}
