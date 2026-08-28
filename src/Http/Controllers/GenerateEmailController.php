<?php

namespace Edstonteam\NmsDevPanel\Http\Controllers;

use Edstonteam\NmsDevPanel\Services\ConsecutiveEmailGenerator;
use Edstonteam\NmsDevPanel\Services\GitBranchResolver;
use Illuminate\Http\JsonResponse;

class GenerateEmailController
{
    public function __invoke(GitBranchResolver $branches, ConsecutiveEmailGenerator $emails): JsonResponse
    {
        return response()->json([
            'email' => $emails->generate($branches->resolve()),
        ]);
    }
}
