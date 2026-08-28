<?php

namespace Edstonteam\NmsDevPanel\Tests\Feature;

use Edstonteam\NmsDevPanel\Services\PaymentConfigurationReconfigurator;
use Edstonteam\NmsDevPanel\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReconfigurePaymentsTest extends TestCase
{
    public function test_it_validates_payment_environment_before_using_the_database(): void
    {
        $this->app['config']->set('nms-dev-panel.payment_settings', [
            ['source' => 'NMSDEV_STRIPE_SECRET', 'value' => null],
        ]);

        $this->expectExceptionMessage('Missing development environment variables: NMSDEV_STRIPE_SECRET');
        (new PaymentConfigurationReconfigurator())->ensureConfigured();
    }

    public function test_it_replaces_payment_settings_for_every_domain(): void
    {
        $this->createTables();
        $this->seedConfigurations();
        $this->configurePackage();

        $result = (new PaymentConfigurationReconfigurator())->reconfigure();

        $this->assertSame(['domains' => 2, 'configurations' => 14], $result);
        $this->assertPaymentConfigurations();
    }

    private function seedConfigurations(): void
    {
        DB::table('domains')->insert([['id' => 1], ['id' => 2]]);
        DB::table('domain_configurations')->insert([
            $this->configuration(1, 'stripe.account_id', 'STRIPE_ACCOUNT_ID', 'old'),
            $this->configuration(2, 'stripe.legacy', 'STRIPE_KEY', 'old'),
            $this->configuration(1, 'mail.from', 'MAIL_FROM', 'keep'),
        ]);
    }

    private function configurePackage(): void
    {
        $this->app['config']->set('nms-dev-panel.domain_model', PaymentDomain::class);
        $this->app['config']->set('nms-dev-panel.domain_configuration_model', PaymentConfiguration::class);
        $this->app['config']->set('nms-dev-panel.payment_settings', $this->settings());
    }

    private function assertPaymentConfigurations(): void
    {
        $keys = array_column($this->settings(), 'config_key');

        $this->assertSame(14, DB::table('domain_configurations')->whereIn('config_key', $keys)->count());
        $this->assertTrue(DB::table('domain_configurations')->where('config_key', 'MAIL_FROM')->exists());
        $this->assertSame(
            'https://example.ngrok.app/api/payment/webhook/ecommpay',
            DB::table('domain_configurations')->where('config_key', 'ECOMMPAY_CUSTOM_WEBHOOK')->value('config_value')
        );
    }

    private function createTables(): void
    {
        Schema::create('domains', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('domain_configurations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('domain_id');
            $table->string('config_path');
            $table->string('config_key');
            $table->text('config_value');
            $table->string('environment');
            $table->timestamps();
        });
    }

    private function settings(): array
    {
        return [
            ['config_path' => 'stripe.account_id', 'config_key' => 'STRIPE_ACCOUNT_ID', 'source' => 'NMSDEV_STRIPE_ACCOUNT_ID', 'value' => 'acct_test'],
            ['config_path' => 'stripe.public_key', 'config_key' => 'STRIPE_KEY', 'source' => 'NMSDEV_STRIPE_KEY', 'value' => 'pk_test'],
            ['config_path' => 'stripe.secret_key.live', 'config_key' => 'STRIPE_SECRET', 'source' => 'NMSDEV_STRIPE_SECRET', 'value' => 'sk_test'],
            ['config_path' => 'stripe.webhook_secret', 'config_key' => 'STRIPE_WEBHOOK_SECRET', 'source' => 'NMSDEV_STRIPE_WEBHOOK_SECRET', 'value' => 'whsec_test'],
            ['config_path' => 'ecommpay.project_id', 'config_key' => 'ECOMMPAY_PROJECT', 'source' => 'NMSDEV_ECOMMPAY_PROJECT', 'value' => '100'],
            ['config_path' => 'ecommpay.secret_key', 'config_key' => 'ECOMMPAY_SECRET', 'source' => 'NMSDEV_ECOMMPAY_SECRET', 'value' => 'eco_test'],
            ['config_path' => 'ecommpay.custom_webhook', 'config_key' => 'ECOMMPAY_CUSTOM_WEBHOOK', 'source' => 'NMSDEV_NGROK_URL', 'value' => 'https://example.ngrok.app/', 'suffix' => '/api/payment/webhook/ecommpay'],
        ];
    }

    private function configuration(int $domainId, string $path, string $key, string $value): array
    {
        return [
            'domain_id' => $domainId,
            'config_path' => $path,
            'config_key' => $key,
            'config_value' => $value,
            'environment' => 'local',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

class PaymentDomain extends Model
{
    protected $table = 'domains';
    public $timestamps = false;
    protected $guarded = [];
}

class PaymentConfiguration extends Model
{
    protected $table = 'domain_configurations';
    protected $guarded = [];
}
