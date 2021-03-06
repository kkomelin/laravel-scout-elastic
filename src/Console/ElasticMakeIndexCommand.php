<?php

namespace ScoutEngines\Elasticsearch\Console;

use Elasticsearch\ClientBuilder;
use Illuminate\Console\Command;

class ElasticMakeIndexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elastic:make-index {index?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make ElasticSearch index, with mapping';

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
     * @return mixed
     */
    public function handle()
    {
        $host = config('elasticsearch.hosts');

        $client = ClientBuilder::create()->setHosts($host)->build();

        $indices = ! is_null($this->argument('index')) ?
            [$this->argument('index')] : array_keys(config('elasticsearch.indices'));

        foreach ($indices as $index) {

            $indexConfig = config("elasticsearch.indices.{$index}");

            if(is_null($indexConfig)) {
                $this->error("Config for index \"{$index}\" not found, skipping...");
                continue;
            }

            if ($client->indices()->exists(['index' => $index])) {
                $this->warn("Index \"{$index}\" exists, deleting!");
                $client->indices()->delete(['index' => $index]);
            }

            $this->info("Creating index: {$index}");
            $client->indices()->create([
                'index' => $index,
                'body' => [
                    "settings" => $indexConfig['settings']
                ]
            ]);

            foreach ($indexConfig['mappings'] ?? [] as $type => $mapping) {

                $this->info("- Creating mapping for: {$type}");
                $client->indices()->putMapping([
                    'index' => $index,
                    'type' => $type,
                    'body' => [
                        'properties' => $mapping
                    ]
                ]);
            }

        }

    }

}
