<?php

namespace App\Console\Commands;

use Illuminate\Support\Facades\DB;

class TempAddIndex2ClickLogDownLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TempAddIndex2ClickDownLog {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add target_type,target_cat,target_id,campaignid unique index';

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
        $type = $this->argument('type');
        if ($type == 'click') {
            $this->changeTableIndex('click');
        } else {
            $this->changeTableIndex('down');
        }
    }
    
    /**
     * 修复表数据，并添加表唯一索引
     * @param string $type
     */
    private function changeTableIndex($type)
    {
        $log = $type . '_log';
        $logTable = 'up_' . $type . '_log';
        $filed = $type . 'id';
        
        $sql = "SELECT target_type,target_cat,target_id,campaignid FROM `{$logTable}` ";
        $sql .= "group by target_id,target_type,target_cat,campaignid ";
        $sql .= "HAVING count(*) > 1";
        $sameList = DB::select($sql);
        DB::transaction(
            function () use ($sameList, $log, $filed, $logTable) {
                foreach ($sameList as $k => $v) {
                    $logList = DB::table($log)->where('target_type', $v->target_type)
                        ->where('target_cat', $v->target_cat)
                        ->where('target_id', $v->target_id)
                        ->where('campaignid', $v->campaignid)
                        ->select($filed)
                        ->get();
                    if ($logList) {
                        foreach ($logList as $lk => $lv) {
                            $sql = "update {$logTable} set target_id = concat(target_id,{$filed}) where {$filed} = ?";
                            DB::update($sql, [
                                $lv->$filed
                            ]);
                        }
                    }
                }
                
                $sql = "ALTER TABLE `{$logTable}` ";
                $sql .= "ADD UNIQUE INDEX `idx_campaigid_target`(`campaignid`,`target_type`,`target_cat`,`target_id`)";
                $sql .= "USING BTREE ";
                DB::update($sql);
            }
        );
    }
}
