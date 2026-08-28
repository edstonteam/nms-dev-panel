<?php

namespace Edstonteam\NmsDevPanel\Tests\Feature;

use Edstonteam\NmsDevPanel\Services\GitBranchResolver;
use Edstonteam\NmsDevPanel\Tests\TestCase;

class PanelTest extends TestCase
{
    public function test_it_injects_the_panel_into_html_responses(): void
    {
        $response = $this->get('/html');

        $response->assertOk();
        $response->assertSee('id="nms-dev-panel"', false);
        $response->assertSee('Generate email');
        $response->assertSee('Configure payments');
        $response->assertSee('Upload dump');
        $response->assertSee('Clear storage &amp; cookies', false);
        $response->assertSee('Close panel');
        $this->assertStringContainsString('nms-dev-panel', $response->getContent());
        $this->assertStringContainsString('[name="email"]', $response->getContent());
        $this->assertStringContainsString('setNativeValue(field,value)', $response->getContent());
        $this->assertStringContainsString('field.blur()', $response->getContent());
        $this->assertStringContainsString("data.append('confirmation','REPLACE')", $response->getContent());
        $this->assertStringContainsString("clearBrowserState();status.textContent='Database replaced and payments configured'", $response->getContent());
        $this->assertStringContainsString('window.location.reload()', $response->getContent());
        $this->assertStringContainsString('panel.remove()', $response->getContent());
        $this->assertStringContainsString('</aside>', $response->getContent());
    }

    public function test_it_displays_the_task_label_and_links_to_jira(): void
    {
        $this->app->instance(GitBranchResolver::class, new class('/tmp') extends GitBranchResolver {
            public function resolve(): string
            {
                return 'epic-WEB-1270';
            }
        });

        $response = $this->get('/html');

        $response->assertSee('Epic 1270');
        $response->assertSee('https://newmindstart.atlassian.net/browse/WEB-1270', false);
        $response->assertDontSee('>DEV<', false);
    }

    public function test_it_does_not_inject_the_panel_into_json_responses(): void
    {
        $this->getJson('/json')
            ->assertOk()
            ->assertExactJson(['ok' => true])
            ->assertDontSee('nms-dev-panel');
    }
}
