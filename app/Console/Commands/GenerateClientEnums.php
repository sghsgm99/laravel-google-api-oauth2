<?php

namespace App\Console\Commands;

use App\Models\Enums\Enumerate;
use Illuminate\Console\Command;

class GenerateClientEnums extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jubilee:generate_client_enums';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate enum file for client side base on backend enums';

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
     * Enum Classes to exclude form the client constants
     *
     * @var array[]
     */
    public static $enumExceptions = [
        Enumerate::class,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /**
         * Generate Enums
         */
        $enums = [];
        $files = \File::allFiles(app_path('Models/Enums'));

        foreach ($files as $file) {
            $property = str_replace('.php', '', $file->getFilename());
            $class = 'App\\Models\\Enums\\' . $property;

            if (in_array($class, self::$enumExceptions)) {
                continue;
            }

            $enums[$property] = $class::clientConsts();
        }

        $enums = json_encode($enums, JSON_PRETTY_PRINT);

        $constantsFileContent = <<<EOF
export const ENUMS = $enums
EOF;

        file_put_contents(resource_path('js/enums.js'), $constantsFileContent);
    }
}
