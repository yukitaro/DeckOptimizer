<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

class AuditScheduledJobs extends Command
{
    protected $signature = 'audit:scheduled-jobs';
    protected $description = 'Audit Laravel scheduled jobs and log their next run time and status';

    public function handle(Schedule $schedule)
    {
        $now = Carbon::now();
        $timezone = config('app.timezone');
        $this->info("🕒 Current time: {$now->format('Y-m-d H:i:s')} ({$timezone})");

        $events = $schedule->events();

        if (empty($events)) {
            $this->warn('⚠️ No scheduled events found.');
            return;
        }

        foreach ($events as $event) {
            $command = $event->description ?? $event->command;
            $nextRun = $event->nextRunDate($now)->format('Y-m-d H:i:s');
            $isDue = $event->isDue(app());

            $this->line("🔧 Command: {$command}");
            $this->line("   ➤ Next run: {$nextRun}");
            $this->line("   ➤ Due now: " . ($isDue ? '✅ Yes' : '❌ No'));
            $this->line("   ➤ Overlapping lock: " . ($event->withoutOverlapping ? '🔒 Enabled' : '🔓 Disabled'));
            $this->line(str_repeat('-', 40));
        }
    }
}
