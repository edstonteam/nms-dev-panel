<?php

namespace Egarrido\NmsDevPanel\Tests\Feature;

use Egarrido\NmsDevPanel\Services\DatabaseDumpReplacer;
use Egarrido\NmsDevPanel\Services\PaymentConfigurationReconfigurator;
use Egarrido\NmsDevPanel\Tests\TestCase;
use Illuminate\Http\UploadedFile;

class ReplaceDatabaseTest extends TestCase
{
    public function test_it_replaces_the_dump_then_configures_payments(): void
    {
        [$replacer, $payments, $state] = $this->fakeServices();
        $this->app->instance(DatabaseDumpReplacer::class, $replacer);
        $this->app->instance(PaymentConfigurationReconfigurator::class, $payments);
        $dump = UploadedFile::fake()->create('backup.sql.gz', 10, 'application/gzip');

        $this->post('/_nms-dev-panel/database', ['dump' => $dump])->assertSessionHasErrors('confirmation');
        $confirmedDump = UploadedFile::fake()->create('backup.sql.gz', 10, 'application/gzip');
        $this->post('/_nms-dev-panel/database', [
            'confirmation' => 'REPLACE',
            'dump' => $confirmedDump,
        ])->assertOk()->assertExactJson([
            'replaced' => true,
            'payments' => ['domains' => 2, 'configurations' => 14],
        ]);

        $this->assertSame(['validate', 'replace:backup.sql.gz', 'reconfigure'], $state->events);
    }

    public function test_it_rejects_unsupported_dump_extensions(): void
    {
        $dump = UploadedFile::fake()->create('backup.php', 10, 'application/x-php');

        $this->post('/_nms-dev-panel/database', [
            'confirmation' => 'REPLACE',
            'dump' => $dump,
        ])->assertSessionHasErrors('dump');
    }

    private function fakeServices(): array
    {
        $state = (object) ['events' => []];

        return [new FakeDatabaseDumpReplacer($state), new FakePaymentReconfigurator($state), $state];
    }
}

class FakeDatabaseDumpReplacer extends DatabaseDumpReplacer
{
    private $state;

    public function __construct($state)
    {
        $this->state = $state;
    }

    public function replace(UploadedFile $dump): void
    {
        $this->state->events[] = 'replace:'.$dump->getClientOriginalName();
    }
}

class FakePaymentReconfigurator extends PaymentConfigurationReconfigurator
{
    private $state;

    public function __construct($state)
    {
        $this->state = $state;
    }

    public function ensureConfigured(): void
    {
        $this->state->events[] = 'validate';
    }

    public function reconfigure(): array
    {
        $this->state->events[] = 'reconfigure';

        return ['domains' => 2, 'configurations' => 14];
    }
}
