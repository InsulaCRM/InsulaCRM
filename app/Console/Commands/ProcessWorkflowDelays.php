<?php

namespace App\Console\Commands;

use App\Services\WorkflowEngine;
use Illuminate\Console\Command;

class ProcessWorkflowDelays extends Command
{
    protected $signature = 'workflow:process-delays';
    protected $description = 'Process delayed workflow steps that are ready to execute';

    public function handle(WorkflowEngine $engine): int
    {
        $processed = $engine->processDelayedSteps();

        $this->info("Processed {$processed} delayed workflow step(s).");

        return Command::SUCCESS;
    }
}
