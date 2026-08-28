<?php

namespace Edstonteam\NmsDevPanel\Http\Middleware;

use Closure;
use Edstonteam\NmsDevPanel\Services\BranchPresenter;
use Edstonteam\NmsDevPanel\Services\GitBranchResolver;
use Illuminate\Http\Request;

class InjectDevPanel
{
    private $branches;
    private $presenter;

    public function __construct(GitBranchResolver $branches, BranchPresenter $presenter)
    {
        $this->branches = $branches;
        $this->presenter = $presenter;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$this->acceptsPanel($response)) {
            return $response;
        }

        $content = $response->getContent();
        $panel = view('nms-dev-panel::panel', $this->panelData())->render();
        $response->setContent(preg_replace('/<\/body>/i', $panel.'</body>', $content, 1));
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function panelData(): array
    {
        $branch = $this->branches->resolve();
        $presentation = $this->presenter->present($branch);

        return [
            'branch' => $branch,
            'branchLabel' => $presentation['label'],
            'jiraUrl' => $presentation['issue'] === null
                ? null
                : rtrim(config('nms-dev-panel.jira_url'), '/').'/'.$presentation['issue'],
        ];
    }

    private function acceptsPanel($response): bool
    {
        if (!method_exists($response, 'getContent') || $response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type');
        $content = (string) $response->getContent();

        return strpos($contentType, 'text/html') !== false
            && stripos($content, '</body>') !== false
            && strpos($content, 'nms-dev-panel') === false;
    }
}
