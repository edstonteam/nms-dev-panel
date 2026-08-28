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
        $response->assertSee('id="nms-dev-panel-loader"', false);
        $response->assertSee('data-nms-loader-title', false);
        $response->assertSee('Applying database dump');
        $response->assertSee('Please keep this page open.');
    }

    public function test_it_injects_panel_behaviour(): void
    {
        $content = $this->get('/html')->assertOk()->getContent();

        $this->assertStringContainsString('nms-dev-panel', $content);
        $this->assertStringContainsString('[name="email"]', $content);
        $this->assertStringContainsString('setNativeValue(field,value)', $content);
        $this->assertStringContainsString('field.blur()', $content);
        $this->assertStringContainsString("data.append('confirmation','REPLACE')", $content);
        $this->assertStringContainsString('showLoader(title,message)', $content);
        $this->assertStringContainsString('hideLoader();throw error', $content);
        $this->assertStringContainsString("clearBrowserState();status.textContent='Database replaced and payments configured'", $content);
        $this->assertStringContainsString('window.location.reload()', $content);
        $this->assertStringContainsString('panel.remove()', $content);
        $this->assertStringContainsString('</aside>', $content);
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
