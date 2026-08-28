<?php

namespace Egarrido\NmsDevPanel\Tests\Unit;

use Egarrido\NmsDevPanel\Services\GitBranchResolver;
use Egarrido\NmsDevPanel\Tests\TestCase;

class GitBranchResolverTest extends TestCase
{
    public function test_it_reads_the_current_branch_from_a_parent_git_directory(): void
    {
        $root = sys_get_temp_dir().'/nms-dev-panel-'.uniqid();
        mkdir($root.'/.git', 0777, true);
        mkdir($root.'/backend');
        file_put_contents($root.'/.git/HEAD', "ref: refs/heads/feature/dynamic-branch\n");

        try {
            $this->assertSame('feature/dynamic-branch', (new GitBranchResolver($root.'/backend'))->resolve());
        } finally {
            unlink($root.'/.git/HEAD');
            rmdir($root.'/.git');
            rmdir($root.'/backend');
            rmdir($root);
        }
    }
}
