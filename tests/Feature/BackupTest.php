<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use App\Support\Backup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_restore_brings_back_the_data(): void
    {
        $trainee = User::factory()->create(['name' => 'Sara']);
        $coach = User::factory()->trainer()->create();
        Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $coach->id, 'package_title' => 'Plan',
            'price' => 25, 'intake' => ['goal' => 'fat_loss'],
        ]);

        $name = Backup::create();

        Subscription::query()->delete();
        $trainee->update(['name' => 'Changed']);
        User::factory()->create();

        $counts = Backup::restore(Backup::path($name));

        $this->assertSame(2, $counts['users']);
        $this->assertSame(1, $counts['subscriptions']);
        $this->assertSame('Sara', $trainee->fresh()->name);
        $this->assertSame(['goal' => 'fat_loss'], Subscription::first()->intake);
        // new rows still get fresh ids after a restore
        $this->assertGreaterThan($coach->id, User::factory()->create()->id);
    }

    public function test_restore_command_saves_current_data_first(): void
    {
        User::factory()->create();
        $name = Backup::create();

        $this->artisan('app:restore', ['file' => $name, '--force' => true])->assertSuccessful();

        $this->assertTrue(Backup::all()->contains(fn ($b) => str_contains($b['name'], 'before-restore')));
    }

    public function test_not_a_backup_is_refused(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'bk');
        file_put_contents($file, gzencode("{\"hello\":1}\n"));
        $this->expectExceptionMessage('not a Himma backup');
        try {
            Backup::restore($file);
        } finally {
            unlink($file);
        }
    }

    public function test_only_the_newest_daily_backups_are_kept(): void
    {
        Storage::disk('local')->makeDirectory(Backup::DIR);
        foreach (range(1, 16) as $day) {
            $path = Backup::DIR . sprintf('/himma-2026-01-%02d-000000.json.gz', $day);
            Storage::disk('local')->put($path, 'x');
            touch(Storage::disk('local')->path($path), strtotime("2026-01-$day"));
        }
        Storage::disk('local')->put(Backup::DIR . '/ameen-gym-2025-12-01-000000-before-restore.json.gz', 'x');

        Backup::prune();

        $names = Backup::all()->pluck('name');
        $this->assertCount(15, $names);
        $this->assertFalse($names->contains('himma-2026-01-02-000000.json.gz'));
        $this->assertTrue($names->contains('himma-2026-01-16-000000.json.gz'));
        $this->assertTrue($names->contains('ameen-gym-2025-12-01-000000-before-restore.json.gz'));
    }
}
