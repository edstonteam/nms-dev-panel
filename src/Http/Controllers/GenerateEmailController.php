<?php

namespace Egarrido\NmsDevPanel\Http\Controllers;

use Egarrido\NmsDevPanel\Services\ConsecutiveEmailGenerator;
use Egarrido\NmsDevPanel\Services\GitBranchResolver;
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
