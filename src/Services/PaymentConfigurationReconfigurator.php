<?php

namespace Edstonteam\NmsDevPanel\Services;

use Illuminate\Support\Carbon;
use RuntimeException;

class PaymentConfigurationReconfigurator
{
    public function ensureConfigured(): void
    {
        $this->validate(config('nms-dev-panel.payment_settings', []));
    }

    public function reconfigure(): array
    {
        $settings = config('nms-dev-panel.payment_settings', []);
        $this->validate($settings);
        $domainModel = config('nms-dev-panel.domain_model');
        $domainIds = (new $domainModel())->newQuery()->pluck('id')->all();

        if ($domainIds === []) {
            return ['domains' => 0, 'configurations' => 0];
        }

        $rows = $this->rows($domainIds, $settings);
        $this->replace($domainIds, $settings, $rows);

        return ['domains' => count($domainIds), 'configurations' => count($rows)];
    }

    private function validate(array $settings): void
    {
        $missing = array_column(array_filter($settings, function (array $setting): bool {
            return $setting['value'] === null || $setting['value'] === '';
        }), 'source');

        if ($missing !== []) {
            throw new RuntimeException('Missing development environment variables: '.implode(', ', $missing));
        }
    }

    private function rows(array $domainIds, array $settings): array
    {
        $rows = [];
        $now = Carbon::now();

        foreach ($domainIds as $domainId) {
            foreach ($settings as $setting) {
                $rows[] = $this->row($domainId, $setting, $now);
            }
        }

        return $rows;
    }

    private function row($domainId, array $setting, $now): array
    {
        return [
            'domain_id' => $domainId,
            'config_path' => $setting['config_path'],
            'config_key' => $setting['config_key'],
            'config_value' => $this->value($setting),
            'environment' => config('nms-dev-panel.payment_environment', 'local'),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function replace(array $domainIds, array $settings, array $rows): void
    {
        $modelClass = config('nms-dev-panel.domain_configuration_model');
        $model = new $modelClass();
        $paths = array_column($settings, 'config_path');
        $keys = array_column($settings, 'config_key');

        $model->getConnection()->transaction(function () use ($model, $domainIds, $paths, $keys, $rows): void {
            $model->newQuery()->whereIn('domain_id', $domainIds)
                ->where(function ($query) use ($paths, $keys): void {
                    $query->whereIn('config_path', $paths)->orWhereIn('config_key', $keys);
                })->delete();
            $model->newQuery()->insert($rows);
        });
    }

    private function value(array $setting): string
    {
        return isset($setting['suffix'])
            ? rtrim((string) $setting['value'], '/').$setting['suffix']
            : (string) $setting['value'];
    }
}
