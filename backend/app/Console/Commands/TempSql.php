<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class TempSql extends Command
{
    /**
     * The name and signature of the console command.
     * Example: php artisan temp_sql "update up_users set email_address = 'test@qq.com' where user_id = 63;"
     * @var string
     */
    protected $signature = 'temp_sql {sql}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'execute sql';

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
        $sql = $this->argument('sql');
        $this->notice($sql);
        $result = DB::statement($sql);
        $this->notice($result);
    }
}
