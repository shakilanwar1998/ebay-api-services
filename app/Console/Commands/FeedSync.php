<?php

namespace App\Console\Commands;

use App\Services\FeedService;
use Illuminate\Console\Command;

class FeedSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'feed:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(FeedService::class)->syncFeedWithDB();
    }
}
