<?php
namespace App\Console\Commands;

use App\Components\Helper\ArrayHelper;
use App\Components\Helper\LogHelper;
use App\Models\Banner;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;

class TempUpdateBannerPackageToAttach extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'temp_update_banner_package_to_attach';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        DB::setFetchMode(\PDO::FETCH_ASSOC);
        $arrayPackageAttach = ArrayHelper::map(
            DB::table('package_files')->get(),
            'id',
            'attach_id'
        );

        $banners = Banner::get();
        LogHelper::notice(count($banners) . ' need to be conducted');
        foreach ($banners as $item) {
            $downloadUrl = $item->download_url;
            if (strlen($downloadUrl) > 15) {
                $arrayUrl = parse_url($downloadUrl);
                if (isset($arrayUrl['query'])) {
                    parse_str($arrayUrl['query']);
                    if (isset($pid) && trim($pid) > 0) {
                        $attachFileId = $arrayPackageAttach[trim($pid)];
                        $item->download_url = substr($item->download_url, 0, strpos($item->download_url, '?') + 1)
                            . http_build_query(['aid' => $attachFileId,]);
                    }
                }
            }
            $item->buildBannerText();

            $item->save();
        }
    }
}
