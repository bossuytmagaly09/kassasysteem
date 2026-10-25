<?php

namespace App\Console\Commands;

use App\Services\BeheerApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pos:beheer-sync {--push-only : Alleen verkopen doorsturen} {--snapshot-only : Alleen snapshot ophalen}')]
#[Description('Synchroniseer snapshot en stuur verkopen door naar beheer.tjeute.be')]
class BeheerSyncCommand extends Command
{
    public function handle(BeheerApiService $service): int
    {
        $this->info('Beheer synchronisatie starten...');

        if (! $this->option('push-only')) {
            $this->info('1. Snapshot ophalen van beheer (events, items, personen, kaarten)...');
            try {
                $snapshotResult = $service->syncSnapshot();
                foreach ($snapshotResult['logs'] as $log) {
                    $this->line("  ✓ {$log}");
                }
            } catch (\Throwable $e) {
                $this->error("Fout bij ophalen snapshot: {$e->getMessage()}");
            }
        }

        if (! $this->option('snapshot-only')) {
            $this->info('2. Openstaande verkopen doorsturen...');
            try {
                $salesResult = $service->syncPendingSales();
                $this->info("  ✓ {$salesResult['message']}");
            } catch (\Throwable $e) {
                $this->error("Fout bij doorsturen verkopen: {$e->getMessage()}");
            }
        }

        $this->info('Klaar!');

        return Command::SUCCESS;
    }
}
