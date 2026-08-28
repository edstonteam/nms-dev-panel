<?php

namespace Edstonteam\NmsDevPanel\Services;

class BranchPresenter
{
    public function present(string $branch): array
    {
        $releaseNumber = $this->releaseNumber($branch);

        if ($releaseNumber !== null) {
            return ['label' => 'Release '.$releaseNumber, 'issue' => 'WEB-'.$releaseNumber];
        }

        $task = $this->parseTask($branch);

        if ($task === null) {
            return ['label' => $branch, 'issue' => null];
        }

        $prefix = $task['prefix'] === '' ? 'Task' : ucfirst(strtolower($task['prefix']));

        return [
            'label' => $prefix.' '.$task['number'],
            'issue' => 'WEB-'.$task['number'],
        ];
    }

    public function number(string $branch): ?string
    {
        $task = $this->parseTask($branch);

        return $task === null ? $this->releaseNumber($branch) : $task['number'];
    }

    private function parseTask(string $branch): ?array
    {
        if (!preg_match('/^(?:(?<prefix>[A-Za-z]+)-)?WEB-(?<number>\d+)$/i', $branch, $matches)) {
            return null;
        }

        return [
            'prefix' => $matches['prefix'] ?? '',
            'number' => $matches['number'],
        ];
    }

    private function releaseNumber(string $branch): ?string
    {
        if (!preg_match('/^release-\d{4}-\d{2}-\d{2}-(?<number>\d+)$/i', $branch, $matches)) {
            return null;
        }

        return $matches['number'];
    }
}
