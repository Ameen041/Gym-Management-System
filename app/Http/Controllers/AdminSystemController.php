<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Backup;
use Illuminate\Support\Facades\Cache;

/** Launch checklist and database backups. */
class AdminSystemController extends Controller
{
    public function index()
    {
        $backups = Backup::all();
        $lastBackup = $backups->first()['time'] ?? null;
        $heartbeat = Cache::get('scheduler:heartbeat');
        $demoUsers = User::where('email', 'like', '%@demo.com')->count();

        // [passed, title, what to do when it isn't]
        $checks = [
            [!config('app.debug'), __('Error details are hidden'),
                __('Set APP_DEBUG=false in .env, otherwise visitors can see technical details when something fails.')],
            [str_starts_with((string) config('app.url'), 'https://'), __('Site address uses https'),
                __('Set APP_URL in .env to the full https address of the site.')],
            [!in_array(config('mail.default'), ['log', 'array'], true), __('Emails are sent'),
                __('Password reset emails are not sent yet. Fill in the MAIL_ settings in .env, or use "Reset link" on the Users page and send it on WhatsApp.')],
            [$demoUsers === 0, __('No demo accounts'),
                trans_choice('{1} There is :count demo account (ending in @demo.com). Delete it from Users.|[2,*] There are :count demo accounts (ending in @demo.com). Delete them from Users.', $demoUsers)],
            [is_writable(storage_path('app')) && is_writable(storage_path('app/public')), __('Uploads can be saved'),
                __('The storage/app folder must be writable by the web server.')],
            [is_link(public_path('storage')) || is_dir(public_path('storage')), __('Coach photos are reachable'),
                __('Run: php artisan storage:link')],
            [$heartbeat && $heartbeat > now()->subMinutes(10)->timestamp, __('Scheduled tasks are running'),
                __('Daily backups need the scheduler. start.sh starts it on the server; elsewhere run: php artisan schedule:work')],
            [$lastBackup && $lastBackup->gt(now()->subDays(2)), __('Recent backup'),
                __('No backup in the last 2 days. Make one below, and download a copy to keep off the server.')],
        ];

        return view('admin.system.index', [
            'checks'  => $checks,
            'passed'  => collect($checks)->where(0, true)->count(),
            'backups' => $backups,
            'isLive'  => app()->environment('production'),
        ]);
    }

    public function backup()
    {
        $name = Backup::create();
        Backup::prune();

        return back()->with('status', __('Backup created: :name', ['name' => $name]));
    }

    public function download(string $name)
    {
        $path = Backup::path($name);
        abort_unless($path, 404);

        return response()->download($path, $name, ['Content-Type' => 'application/gzip']);
    }

    public function destroy(string $name)
    {
        abort_unless(Backup::delete($name), 404);

        return back()->with('status', __('Backup deleted.'));
    }
}
