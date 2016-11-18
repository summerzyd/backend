<?php
namespace App\Console\Commands;

use App\Components\Helper\HttpClientHelper;
use App\Components\Helper\LogHelper;
use App\Models\DataSummaryAdxDaily;
use App\Components\Config;

class JobDataAdx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'job_data_adx {--start-date=} {--end-date=}';

    /**
     * The console command description.
     *获取每日报表邮件
     * @var string
     */
    protected $description = 'Command for send email about daily report';

    protected $dspid;
    protected $token;
    protected $type;
    protected $url;
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
     * @return mixed
     */
    public function handle()
    {
        $start = $this->option('start-date') ? $this->option('start-date') : date("Y-m-d", strtotime("-1 day"));
        $end = $this->option('end-date') ? $this->option('end-date') : date("Y-m-d", strtotime("-1 day"));
        $this->updateYoukuData($start, $end);
    }
    public function updateYoukuData($start, $end)
    {
        $this->afid = Config::get('biddingos.adx.youku.afid');
        $this->dspid = Config::get('biddingos.adx.youku.dspid');
        $this->token = Config::get('biddingos.adx.youku.token');
        $this->url = Config::get('biddingos.adx.youku.prefix_url');
        $post_data = [
            'dspid' => strval($this->dspid),
            'token' => $this->token,
            'type' =>  "detail",
            'startdate' => $start,
            'enddate' => $end,
        ];
        $result = HttpClientHelper::call($this->url."/report", json_encode($post_data));
        $this->info('youku : ' . $this->url . " : " . json_encode($post_data));
        $this->info('youku return: ' . $result);
        $data = json_decode($result, true)['message']['records'];
        if (sizeof($data) > 0) {
            foreach ($data as $key => $val) {
                foreach ($val as $item) {
                    $model = DataSummaryAdxDaily::whereMulti([
                        'affiliateid' => $this->afid,
                        'external_zone_id' => $item['aid'],
                        'date' => $key
                    ])->first();
                    if ($model) {
                        $model->bid_number = $item['bid'];
                        $model->win_number = $item['winbid'];
                        $model->impressions = $item['pv'];
                        $model->af_income = $item['cost'];
                        $model->clicks = $item['click'];
                    } else {
                        $model = new DataSummaryAdxDaily(
                            [
                                'bid_number' => $item['bid'],
                                'win_number' => $item['winbid'],
                                'impressions' => $item['pv'],
                                'af_income' => $item['cost'],
                                'clicks' => $item['click'],
                                'external_zone_id' => $item['aid'],
                                'date' => $key,
                                'affiliateid' => $this->afid,
                            ]
                        );
                    }
                    $ret = $model->save();
                    if (!$ret) {
                        LogHelper::error("Update data_adx 更新
                        affiliateid={$this->afid}
                        external_zone_id={$item['aid']}
                        date={$key}失败");
                    }
                }
            }
        }

    }
}
