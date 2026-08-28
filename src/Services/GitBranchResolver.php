<?php

namespace Egarrido\NmsDevPanel\Services;

class GitBranchResolver
{
    private $workingDirectory;

    public function __construct(string $workingDirectory)
    {
        $this->workingDirectory = $workingDirectory;
    }

    public function resolve(): string
    {
        $gitDirectory = $this->findGitDirectory($this->workingDirectory);

        if ($gitDirectory === null) {
            return 'unknown';
        }

        $head = trim((string) @file_get_contents($gitDirectory.'/HEAD'));

        if (strpos($head, 'ref: refs/heads/') === 0) {
            return substr($head, strlen('ref: refs/heads/'));
        }

        return $head !== '' ? substr($head, 0, 7) : 'unknown';
    }

    private function findGitDirectory(string $path): ?string
    {
        $path = realpath($path) ?: $path;

        while ($path !== dirname($path)) {
            $gitPath = $path.'/.git';

            if (is_dir($gitPath)) {
                return $gitPath;
            }

            if (is_file($gitPath)) {
                return $this->resolveGitFile($gitPath);
            }

            $path = dirname($path);
        }

        return null;
    }

    private function resolveGitFile(string $gitFile): ?string
    {
        $contents = trim((string) @file_get_contents($gitFile));

        if (strpos($contents, 'gitdir: ') !== 0) {
            return null;
        }

        $path = substr($contents, strlen('gitdir: '));
        $gitDirectory = strpos($path, '/') === 0 ? $path : dirname($gitFile).'/'.$path;

        return realpath($gitDirectory) ?: null;
    }
}
