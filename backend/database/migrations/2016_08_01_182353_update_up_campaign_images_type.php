<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateUpCampaignImagesType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $images = \DB::table('campaigns_images')->get(['id','url']);
        foreach ($images as $item) {
            \DB::table('campaigns_images')->where('id', $item->id)->update([
               'type' => \App\Models\CampaignImage::getImageType($item->url)
            ]);
        }
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
