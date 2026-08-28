<?php

namespace Edstonteam\NmsDevPanel\Tests\Feature;

use Edstonteam\NmsDevPanel\Services\GitBranchResolver;
use Edstonteam\NmsDevPanel\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GenerateEmailTest extends TestCase
{
    public function test_it_generates_the_next_numeric_email_for_the_current_task(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
        });
        DB::table('users')->insert([
            ['email' => '1271+1@local.test'],
            ['email' => '1271+7@local.test'],
            ['email' => '1271+invalid@local.test'],
            ['email' => '1272+20@local.test'],
        ]);
        $this->app['config']->set('nms-dev-panel.user_model', TestUser::class);
        $this->app->instance(GitBranchResolver::class, new class('/tmp') extends GitBranchResolver {
            public function resolve(): string
            {
                return 'bug-WEB-1271';
            }
        });

        $this->postJson('/_nms-dev-panel/email')
            ->assertOk()
            ->assertExactJson(['email' => '1271+8@local.test']);
    }
}

class TestUser extends Model
{
    protected $table = 'users';
    public $timestamps = false;
    protected $guarded = [];
}
