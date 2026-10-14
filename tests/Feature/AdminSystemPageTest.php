<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSystemPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_admin_sees_checklist_and_manages_backups(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.system.index'))->assertOk()->assertSee(__('Ready to launch'));

        $this->actingAs($admin)->post(route('admin.system.backup'))->assertSessionHas('status');
        $name = Backup::all()->first()['name'];

        $this->actingAs($admin)->get(route('admin.system.download', $name))->assertOk()->assertDownload($name);
        $this->actingAs($admin)->delete(route('admin.system.destroy', $name))->assertSessionHas('status');
        $this->assertCount(0, Backup::all());
    }

    public function test_backups_are_admin_only_and_names_are_checked(): void
    {
        $trainee = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $name = Backup::create();

        $this->actingAs($trainee)->get(route('admin.system.download', $name))->assertRedirect(route('trainee.dashboard'));
        $this->actingAs($admin)->get('/admin/system/backups/..%2F..%2F.env')->assertNotFound();
        $this->actingAs($admin)->get(route('admin.system.download', 'nope.json.gz'))->assertNotFound();
    }
}
