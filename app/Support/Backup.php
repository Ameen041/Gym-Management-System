<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Database backups as gzipped JSON lines, kept in storage/app/backups.
 *
 * The format does not depend on the database engine, so a backup taken on
 * SQLite (the local copy) can be restored on MySQL / PostgreSQL and back.
 * Line 1 is a header, every other line is one row: {"t": table, "r": row}.
 */
class Backup
{
    public const DIR = 'backups';
    public const KEEP = 14;
    private const FORMAT = 'himma-backup';
    private const OLD_FORMATS = ['ameen-gym-backup'];   // backups made before the rename

    // Rebuilt by the app on its own; never worth restoring.
    private const SKIP_TABLES = [
        'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
        'password_resets', 'password_reset_tokens', 'personal_access_tokens',
    ];

    public static function disk()
    {
        return Storage::disk('local');
    }

    /** Write a new backup and return its file name. */
    public static function create(string $label = ''): string
    {
        $name = 'himma-' . now()->format('Y-m-d-His') . ($label ? "-$label" : '') . '.json.gz';
        $disk = self::disk();
        $disk->makeDirectory(self::DIR);
        $path = $disk->path(self::DIR . '/' . $name);
        $tmp = $path . '.part';

        $gz = gzopen($tmp, 'wb6');
        if ($gz === false) {
            throw new RuntimeException("Cannot write $tmp");
        }

        try {
            $tables = self::tables();
            $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
            gzwrite($gz, json_encode([
                'format'     => self::FORMAT,
                'version'    => 1,
                'created_at' => now()->toIso8601String(),
                'driver'     => DB::getDriverName(),
                'tables'     => $tables,
            ], $flags) . "\n");

            foreach ($tables as $table) {
                foreach (DB::table($table)->cursor() as $row) {
                    gzwrite($gz, json_encode(['t' => $table, 'r' => $row], $flags) . "\n");
                }
            }
        } finally {
            gzclose($gz);
        }

        rename($tmp, $path);

        return $name;
    }

    /**
     * Replace the data of every table in the backup with the backup's rows.
     * Runs in one transaction: if anything fails, nothing changes.
     *
     * @return array<string,int> rows restored per table
     */
    public static function restore(string $file): array
    {
        $gz = gzopen($file, 'rb');
        if ($gz === false) {
            throw new RuntimeException("Cannot read $file");
        }

        try {
            $header = json_decode((string) gzgets($gz), true);
            if (!in_array($header['format'] ?? null, [self::FORMAT, ...self::OLD_FORMATS], true)) {
                throw new RuntimeException('This file is not a Himma backup.');
            }

            // the target database decides the order: parent tables before the tables pointing at them
            $tables = array_values(array_intersect(self::tables(), $header['tables'] ?? []));
            $columns = [];
            foreach ($tables as $table) {
                $columns[$table] = array_flip(Schema::getColumnListing($table));
            }

            // 1) split the rows per table into temporary files
            $tmpDir = sys_get_temp_dir() . '/himma-restore-' . bin2hex(random_bytes(6));
            mkdir($tmpDir, 0700);
            $parts = [];
            while (($line = gzgets($gz)) !== false) {
                $table = json_decode($line, true)['t'] ?? null;
                if (isset($columns[$table])) {
                    $parts[$table] ??= fopen("$tmpDir/$table.jsonl", 'w');
                    fwrite($parts[$table], $line);
                }
            }
            array_map('fclose', $parts);
        } finally {
            gzclose($gz);
        }

        $counts = array_fill_keys($tables, 0);

        // 2) replace the data. Foreign keys are switched off where the database
        //    allows it (outside the transaction: SQLite ignores it inside one).
        Schema::disableForeignKeyConstraints();
        DB::beginTransaction();
        try {
            foreach (array_reverse($tables) as $table) {
                DB::table($table)->delete();
            }

            foreach ($tables as $table) {
                $file = "$tmpDir/$table.jsonl";
                if (!is_file($file)) {
                    continue;
                }
                $fh = fopen($file, 'r');
                $batch = [];
                while (($line = fgets($fh)) !== false) {
                    // columns the current schema no longer has are dropped
                    $batch[] = array_intersect_key(json_decode($line, true)['r'], $columns[$table]);
                    if (count($batch) === 200) {
                        DB::table($table)->insert($batch);
                        $counts[$table] += 200;
                        $batch = [];
                    }
                }
                fclose($fh);
                if ($batch) {
                    DB::table($table)->insert($batch);
                    $counts[$table] += count($batch);
                }
            }

            if (DB::getDriverName() === 'pgsql') {
                self::resetPostgresSequences($tables, $columns);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            Schema::enableForeignKeyConstraints();
            array_map('unlink', glob("$tmpDir/*") ?: []);
            @rmdir($tmpDir);
        }

        return $counts;
    }

    /** Backups on disk, newest first: name, size (bytes), time. */
    public static function all(): Collection
    {
        $disk = self::disk();

        return collect($disk->files(self::DIR))
            ->filter(fn ($p) => str_ends_with($p, '.json.gz'))
            ->map(fn ($p) => [
                'name' => basename($p),
                'size' => $disk->size($p),
                'time' => \Carbon\Carbon::createFromTimestamp($disk->lastModified($p)),
            ])
            ->sortByDesc('time')->values();
    }

    /** Full path of a backup by its file name, or null if there is no such backup. */
    public static function path(string $name): ?string
    {
        if (basename($name) !== $name || !str_ends_with($name, '.json.gz')) {
            return null;
        }
        $path = self::DIR . '/' . $name;

        return self::disk()->exists($path) ? self::disk()->path($path) : null;
    }

    public static function delete(string $name): bool
    {
        return self::path($name) !== null && self::disk()->delete(self::DIR . '/' . $name);
    }

    /** Keep the newest $keep daily backups; named ones (e.g. "before-restore") are left alone. */
    public static function prune(int $keep = self::KEEP): void
    {
        self::all()
            ->filter(fn ($b) => preg_match('/^(himma|ameen-gym)-\d{4}-\d\d-\d\d-\d{6}\.json\.gz$/', $b['name']))
            ->slice($keep)
            ->each(fn ($b) => self::delete($b['name']));
    }

    /** @return string[] tables of the app, without the ones the app rebuilds by itself */
    public static function tables(): array
    {
        $names = match (DB::getDriverName()) {
            'sqlite' => collect(DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))->pluck('name'),
            'mysql'  => collect(DB::select('select table_name as name from information_schema.tables where table_schema = database() and table_type = ?', ['BASE TABLE']))->pluck('name'),
            'pgsql'  => collect(DB::select('select tablename as name from pg_tables where schemaname = current_schema()'))->pluck('name'),
            default  => throw new RuntimeException('Backups are not supported on ' . DB::getDriverName()),
        };

        $names = $names->reject(fn ($t) => in_array($t, self::SKIP_TABLES, true))->sort()->values()->all();

        return self::parentsFirst($names, self::foreignKeys());
    }

    /** @return array<int,array{0:string,1:string}> [child table, parent table] pairs */
    private static function foreignKeys(): array
    {
        $rows = match (DB::getDriverName()) {
            'sqlite' => collect(DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"))
                ->flatMap(fn ($t) => collect(DB::select('select "table" as parent from pragma_foreign_key_list(?)', [$t->name]))
                    ->map(fn ($fk) => (object) ['child' => $t->name, 'parent' => $fk->parent])),
            'mysql'  => collect(DB::select('select table_name as child, referenced_table_name as parent from information_schema.key_column_usage
                where table_schema = database() and referenced_table_name is not null')),
            'pgsql'  => collect(DB::select("select tc.table_name as child, ccu.table_name as parent
                from information_schema.table_constraints tc
                join information_schema.constraint_column_usage ccu on ccu.constraint_name = tc.constraint_name and ccu.constraint_schema = tc.constraint_schema
                where tc.constraint_type = 'FOREIGN KEY' and tc.table_schema = current_schema()")),
        };

        return $rows->map(fn ($r) => [$r->child, $r->parent])->all();
    }

    /** Sort tables so every table comes after the tables it points at. */
    private static function parentsFirst(array $tables, array $edges): array
    {
        $parents = array_fill_keys($tables, []);
        foreach ($edges as [$child, $parent]) {
            if ($child !== $parent && isset($parents[$child], $parents[$parent])) {
                $parents[$child][$parent] = true;
            }
        }

        $ordered = [];
        while ($parents) {
            $ready = array_keys(array_filter($parents, fn ($p) => !array_diff_key($p, array_flip($ordered))));
            if (!$ready) {
                // a cycle: keep the rest as they are
                $ready = array_keys($parents);
            }
            foreach ($ready as $table) {
                $ordered[] = $table;
                unset($parents[$table]);
            }
        }

        return $ordered;
    }

    private static function resetPostgresSequences(array $tables, array $columns): void
    {
        foreach ($tables as $table) {
            // only auto-increment ids have a sequence (not uuid ones, e.g. notifications)
            $sequence = isset($columns[$table]['id'])
                ? DB::selectOne("select pg_get_serial_sequence(?, 'id') as seq", [$table])->seq
                : null;
            if ($sequence) {
                DB::statement("select setval(?, coalesce((select max(id) from \"$table\"), 0) + 1, false)", [$sequence]);
            }
        }
    }
}
