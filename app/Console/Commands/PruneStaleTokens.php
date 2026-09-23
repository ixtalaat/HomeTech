<?php

namespace App\Console\Commands;

use App\Services\FcmService;
use Illuminate\Console\Command;

class PruneStaleTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:prune-stale-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate stored push tokens with FCM and delete the dead ones';

    /**
     * Execute the console command.
     */
    public function handle(FcmService $push): int
    {
        $result = $push->pruneStaleTokens();

        $this->info("Checked {$result['checked']} token(s), pruned {$result['pruned']} stale.");

        return self::SUCCESS;
    }
}
