<?php

namespace App\Console\Commands;

use App\Components\Helper\LogHelper;
use App\Models\Banner;

class TempUpdateBannerStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_banner_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fix banner status';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        $banners = Banner::where('status', Banner::STATUS_SUSPENDED)
            ->where('pause_status', 1)
            ->select('status', 'pause_status', 'an_status', 'an_pause_status', 'bannerid')
            ->get();

        if ($banners) {
            foreach ($banners as $item) {
                LogHelper::notice('bannerid ' . $item->bannerid . ' fix before state ['
                    . $item->status . '] pause_status [' . $item->pause_status . ']');

                if (($item->an_status == 1 && $item->an_pause_status == 1) ||
                    ($item->an_status == 0 && $item->an_status == null)
                ) {
                    //campaign暂停前是campaign暂停或者未知时，banner状态改为投放中
                    Banner::where('bannerid', $item->bannerid)->update([
                        'status' => 0,
                        'pause_status' => 0,
                        'an_status' => 0,
                        'an_pause_status' => 0,
                    ]);
                } elseif ($item->an_status == 4) {
                    //campaign暂停前是待验证，banner状态改为未通过审核
                    Banner::where('bannerid', $item->bannerid)->update([
                        'status' => 3,
                        'pause_status' => 0,
                        'an_status' => 0,
                        'an_pause_status' => 0,
                    ]);
                } else {
                    //其他更改为之前状态
                    Banner::where('bannerid', $item->bannerid)->update([
                        'status' => $item->an_status,
                        'pause_status' => $item->an_pause_status,
                        'an_status' => 0,
                        'an_pause_status' => 0,
                    ]);
                }
                LogHelper::notice('bannerid ' . $item->bannerid . ' fix before state ['
                    . $item->an_status . '] pause_status [' . $item->an_pause_status . ']');
            }
        }
    }
}
