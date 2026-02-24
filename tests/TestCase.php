<?php

declare(strict_types=1);

namespace Reker7\MoonShineBlocks\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\Laravel\MoonShineRequest;
use MoonShine\Laravel\Providers\MoonShineServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Reker7\MoonShineBlocks\Providers\MoonShineBlocksServiceProvider;
use Reker7\MoonShineBlocksCore\Providers\BlocksCoreServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected MoonshineUser $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = MoonshineUser::factory()->create([
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
        ])->load('moonshineUserRole');
    }

    protected function defineEnvironment($app): void
    {
        // Ensure MoonShineRequest is resolved from the current request (not an empty fresh instance).
        // MoonShineServiceProvider only binds it to CrudRequestContract, not to MoonShineRequest::class.
        $app->bind(MoonShineRequest::class, static fn ($app) => MoonShineRequest::createFrom($app['request']));

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('app.debug', true);
        $app['config']->set('moonshine.cache', 'array');
        $app['config']->set('moonshine.use_migrations', true);
        $app['config']->set('moonshine.use_notifications', false);
        $app['config']->set('moonshine.use_database_notifications', false);
        $app['config']->set('moonshine.auth.enabled', true);
    }

    protected function getPackageProviders($app): array
    {
        return [
            MoonShineServiceProvider::class,
            BlocksCoreServiceProvider::class,
            MoonShineBlocksServiceProvider::class,
        ];
    }

    protected function actingAsAdmin(): static
    {
        return $this->actingAs($this->adminUser, 'moonshine');
    }
}
