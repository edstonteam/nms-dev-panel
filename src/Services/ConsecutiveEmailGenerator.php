<?php

namespace Edstonteam\NmsDevPanel\Services;

use RuntimeException;

class ConsecutiveEmailGenerator
{
    private $presenter;

    public function __construct(BranchPresenter $presenter)
    {
        $this->presenter = $presenter;
    }

    public function generate(string $branch): string
    {
        $number = $this->presenter->number($branch);

        if ($number === null) {
            throw new RuntimeException('The current branch does not contain a WEB task number.');
        }

        $domain = config('nms-dev-panel.email_domain');
        $suffix = $this->nextSuffix($number, $domain);

        return sprintf('%s+%d@%s', $number, $suffix, $domain);
    }

    private function nextSuffix(string $number, string $domain): int
    {
        $model = config('nms-dev-panel.user_model');
        $emails = (new $model())->newQuery()
            ->where('email', 'like', $number.'+%@'.$domain)
            ->pluck('email');
        $pattern = '/^'.preg_quote($number, '/').'\+(\d+)@'.preg_quote($domain, '/').'$/i';
        $highest = 0;

        foreach ($emails as $email) {
            if (preg_match($pattern, $email, $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        return $highest + 1;
    }
}
