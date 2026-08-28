<?php

namespace Edstonteam\NmsDevPanel\Tests\Unit;

use Edstonteam\NmsDevPanel\Services\BranchPresenter;
use PHPUnit\Framework\TestCase;

class BranchPresenterTest extends TestCase
{
    public function test_it_presents_task_branches(): void
    {
        $branches = [
            ['epic-WEB-1270', 'Epic 1270', 'WEB-1270'],
            ['bug-WEB-1271', 'Bug 1271', 'WEB-1271'],
            ['WEB-1277', 'Task 1277', 'WEB-1277'],
        ];

        foreach ($branches as [$branch, $label, $issue]) {
            $this->assertSame([
                'label' => $label,
                'issue' => $issue,
            ], (new BranchPresenter())->present($branch));
        }
    }

    public function test_it_extracts_only_the_task_number(): void
    {
        $presenter = new BranchPresenter();

        $this->assertSame('1270', $presenter->number('epic-WEB-1270'));
        $this->assertSame('1277', $presenter->number('WEB-1277'));
        $this->assertNull($presenter->number('develop'));
    }

    public function test_it_presents_release_branches_with_a_jira_issue(): void
    {
        $presenter = new BranchPresenter();

        $this->assertSame([
            'label' => 'Release 1314',
            'issue' => 'WEB-1314',
        ], $presenter->present('release-2026-08-28-1314'));
        $this->assertSame('1314', $presenter->number('release-2026-08-28-1314'));
    }

    public function test_it_preserves_an_unrecognised_branch_without_a_jira_issue(): void
    {
        $this->assertSame([
            'label' => 'develop',
            'issue' => null,
        ], (new BranchPresenter())->present('develop'));
    }
}
