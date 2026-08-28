<?php

namespace Egarrido\NmsDevPanel\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseDumpReplacer
{
    public function replace(UploadedFile $dump): void
    {
        set_time_limit((int) config('nms-dev-panel.database_dump.timeout', 900) + 30);
        $this->assertSupported($dump);
        [$name, $connection] = $this->connection();
        [$stream, $compressed] = $this->open($dump);

        try {
            $this->recreate($connection);
            $this->import($connection, $stream);
        } finally {
            $compressed ? gzclose($stream) : fclose($stream);
        }

        app('db')->purge($name);
    }

    private function assertSupported(UploadedFile $dump): void
    {
        if (!preg_match('/\.sql(?:\.gz)?$/i', $dump->getClientOriginalName())) {
            throw new RuntimeException('Only .sql and .sql.gz database dumps are supported.');
        }
    }

    private function connection(): array
    {
        $name = config('database.default');
        $connection = config('database.connections.'.$name);

        if (($connection['driver'] ?? null) !== 'mysql' || empty($connection['database'])) {
            throw new RuntimeException('Database replacement requires a configured MySQL connection.');
        }

        return [$name, $connection];
    }

    private function open(UploadedFile $dump): array
    {
        $path = $dump->getRealPath();

        if ($path === false) {
            throw new RuntimeException('The uploaded database dump could not be opened.');
        }

        $compressed = preg_match('/\.gz$/i', $dump->getClientOriginalName()) === 1;
        $stream = $compressed ? gzopen($path, 'rb') : fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('The uploaded database dump could not be opened.');
        }

        return [$stream, $compressed];
    }

    private function recreate(array $connection): void
    {
        $database = str_replace('`', '``', $connection['database']);
        $charset = $this->option($connection['charset'] ?? 'utf8mb4');
        $collation = $this->option($connection['collation'] ?? 'utf8mb4_unicode_ci');
        $sql = "DROP DATABASE IF EXISTS `{$database}`; CREATE DATABASE `{$database}` CHARACTER SET {$charset} COLLATE {$collation};";

        $this->run(array_merge($this->command($connection), ['--execute='.$sql]), $connection, null, 'recreate');
    }

    private function import(array $connection, $stream): void
    {
        $command = array_merge($this->command($connection), ['--binary-mode=1', '--database='.$connection['database']]);

        $this->run($command, $connection, $stream, 'import');
    }

    private function command(array $connection): array
    {
        $command = [config('nms-dev-panel.database_dump.binary', 'mysql'), '--user='.(string) ($connection['username'] ?? '')];

        if (!empty($connection['unix_socket'])) {
            $command[] = '--socket='.$connection['unix_socket'];
        } else {
            $command[] = '--host='.(string) ($connection['host'] ?? '127.0.0.1');
            $command[] = '--port='.(string) ($connection['port'] ?? '3306');
            $command[] = '--protocol=TCP';
        }

        return $command;
    }

    private function run(array $command, array $connection, $input, string $phase): void
    {
        $process = new Process($command, null, ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
        $process->setTimeout((float) config('nms-dev-panel.database_dump.timeout', 900));
        $process->setInput($input)->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException('Database '.$phase.' failed with exit code '.$process->getExitCode().'.');
        }
    }

    private function option(string $value): string
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', $value)) {
            throw new RuntimeException('The configured database charset or collation is invalid.');
        }

        return $value;
    }
}
