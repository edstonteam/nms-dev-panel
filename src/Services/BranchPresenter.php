<?php

namespace Edstonteam\NmsDevPanel\Services;

class BranchPresenter
{
    public function present(string $branch): array
    {
        $task = $this->parse($branch);

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
        $task = $this->parse($branch);

        return $task === null ? null : $task['number'];
    }

    private function parse(string $branch): ?array
    {
        if (!preg_match('/^(?:(?<prefix>[A-Za-z]+)-)?WEB-(?<number>\d+)$/i', $branch, $matches)) {
            return null;
        }

        return [
            'prefix' => $matches['prefix'] ?? '',
            'number' => $matches['number'],
        ];
    }
}
