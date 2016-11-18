<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InsertDataUpCampaignHistoryRevenue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $sql = <<<SQL
INSERT INTO up_campaign_revenue_history (
	campaignid,
	time,
	history_revenue,
	current_revenue
) SELECT
	campaignid,
	created,
	revenue,
	revenue
FROM
	up_campaigns
WHERE
	campaignid NOT IN (
		SELECT DISTINCT
			(campaignid)
		FROM
			`up_campaign_revenue_history`
	)
AND `status` IN (0, 1, 15);
SQL;
        DB::getPdo()->exec($sql);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
