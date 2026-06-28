<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Chronicle\Entry\Entry;
use Chronicle\Facades\Chronicle;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    private function seedEntries(): array
    {
        $actor = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();

        Chronicle::record()->actor($actor)->action('audit.test_alpha')->subject($actor)->commit();
        Chronicle::record()->actor($actor)->action('audit.test_beta')->subject($other)->commit();
        Chronicle::record()->actor($other)->action('audit.test_gamma')->subject($other)->commit();

        return ['actor' => $actor, 'other' => $other];
    }

    #[Test]
    public function index_requires_audit_view_permission(): void
    {
        $admin = $this->adminWithPermissions([]);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/audit-logs')
            ->assertStatus(403);
    }

    #[Test]
    public function index_returns_paginated_entries(): void
    {
        $this->seedEntries();
        $viewer = $this->adminWithPermissions(['audit.view']);

        $response = $this->asToken($this->tokenFor($viewer))->getJson('/api/admin/audit-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [['id', 'action', 'actor', 'subject', 'metadata', 'tags', 'created_at']],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ]);

        $this->assertGreaterThanOrEqual(3, $response->json('data.meta.total'));
    }

    #[Test]
    public function index_filters_by_actor(): void
    {
        ['actor' => $actor] = $this->seedEntries();
        $viewer = $this->adminWithPermissions(['audit.view']);

        $response = $this->asToken($this->tokenFor($viewer))->getJson(
            '/api/admin/audit-logs?'.http_build_query([
                'actor_type' => $actor::class,
                'actor_id' => $actor->id,
            ])
        );

        $response->assertOk();
        $actions = collect($response->json('data.data'))->pluck('action')->all();
        $this->assertContains('audit.test_alpha', $actions);
        $this->assertContains('audit.test_beta', $actions);
        $this->assertNotContains('audit.test_gamma', $actions);
    }

    #[Test]
    public function index_filters_by_subject(): void
    {
        ['other' => $other] = $this->seedEntries();
        $viewer = $this->adminWithPermissions(['audit.view']);

        $response = $this->asToken($this->tokenFor($viewer))->getJson(
            '/api/admin/audit-logs?'.http_build_query([
                'subject_type' => $other::class,
                'subject_id' => $other->id,
            ])
        );

        $response->assertOk();
        $actions = collect($response->json('data.data'))->pluck('action')->all();
        $this->assertContains('audit.test_beta', $actions);
        $this->assertContains('audit.test_gamma', $actions);
        $this->assertNotContains('audit.test_alpha', $actions);
    }

    #[Test]
    public function index_filter_requires_both_actor_type_and_actor_id(): void
    {
        $viewer = $this->adminWithPermissions(['audit.view']);

        $this->asToken($this->tokenFor($viewer))
            ->getJson('/api/admin/audit-logs?actor_id=123')
            ->assertStatus(422);
    }

    #[Test]
    public function show_returns_full_entry(): void
    {
        ['actor' => $actor] = $this->seedEntries();
        $viewer = $this->adminWithPermissions(['audit.view']);

        $entry = Entry::query()->action('audit.test_alpha')->firstOrFail();

        $response = $this->asToken($this->tokenFor($viewer))
            ->getJson("/api/admin/audit-logs/{$entry->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $entry->id)
            ->assertJsonPath('data.action', 'audit.test_alpha');
    }

    #[Test]
    public function show_returns_404_for_unknown_entry(): void
    {
        $viewer = $this->adminWithPermissions(['audit.view']);

        $this->asToken($this->tokenFor($viewer))
            ->getJson('/api/admin/audit-logs/01XYZNOTAREALID00000000000')
            ->assertStatus(404);
    }
}
