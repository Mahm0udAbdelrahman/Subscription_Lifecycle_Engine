<?php

namespace App\Console\Commands;

use App\Services\Api\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptionLifecycle extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'subscription:process-lifecycle';

    /**
     * The console command description.
     */
    protected $description = 'Process subscription lifecycle: expire trials and cancel past-due subscriptions that exceeded their grace period.';

    public function __construct(
        protected SubscriptionService $subscriptionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing subscription lifecycle...');
        $this->newLine();

        $this->info('→ Checking expired trials...');
        $expiredTrials = $this->subscriptionService->processExpiredTrials();
        $this->line("  Canceled {$expiredTrials} expired trial(s).");

        $this->newLine();

        $this->info('→ Checking expired grace periods...');
        $expiredGracePeriods = $this->subscriptionService->processExpiredGracePeriods();
        $this->line("  Canceled {$expiredGracePeriods} past-due subscription(s) with expired grace period.");

        $this->newLine();
        $this->info('✓ Subscription lifecycle processing complete.');
        $this->table(
            ['Action', 'Count'],
            [
                ['Expired trials canceled', $expiredTrials],
                ['Expired grace periods canceled', $expiredGracePeriods],
            ]
        );

        return self::SUCCESS;
    }
}
