<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanUpArchiveArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:cleanup_archive_articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all archive articles more than 48 hours';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $last_48_hours = now()->subHours(48)->toDateTimeString();
        $tables = [
            'article_site',
            'article_site_category',
            'article_site_menu',
            'article_site_tag',
        ];

        Article::onlyTrashed()->whereDate('deleted_at', '<=', $last_48_hours)->each(function($article) use ($tables) {
            foreach ($tables as $table) {
                DB::table($table)
                    ->where('article_id', $article->id)
                    ->delete();
            }

            $article->Service()->forceDelete();
        }, 200);

        $this->info('Deleting archived articles successful.');

        return CommandAlias::SUCCESS;
    }
}
