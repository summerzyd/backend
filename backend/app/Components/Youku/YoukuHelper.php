<?php
namespace App\Components\Youku;

use App\Components\Config;
use App\Components\Helper\LogHelper;

class YoukuHelper
{
    public static function youKuUpload($file_name, $title)
    {
        header('Content-type: text/html; charset=utf-8');
        $client_id = Config::get('biddingos.adx.youku.clientid');
        $client_secret = Config::get('biddingos.adx.youku.client_secret');
        $params['access_token'] = Config::get('biddingos.adx.youku.access_token');
        $params['refresh_token'] = Config::get('biddingos.adx.youku.refresh_token');
        $params['username'] = ""; //Youku username or email
        $params['password'] = md5(""); //Youku password

        set_time_limit(0);
        ini_set('memory_limit', '128M');
        $youkuUploader = new YoukuUploader($client_id, $client_secret);
        try {
            $file_md5 = @md5_file($file_name);
            if (!$file_md5) {
                throw new \Exception("Could not open the file!\n");
            }
        } catch (\Exception $e) {
            LogHelper::error("(File: " . $e->getFile() . ", line " . $e->getLine() . "): " . $e->getMessage());
            return;
        }
        $file_size = filesize($file_name);
        $uploadInfo = array(
            "title" => $title, //video title
            "tags" => "贴片广告", //tags, split by space
            "file_name" => $file_name, //video file name
            "file_md5" => $file_md5, //video file's md5sum
            "file_size" => $file_size //video file size
        );
        $progress = true; //if true,show the uploading progress
        return $youkuUploader->upload($progress, $params, $uploadInfo);
    }
}
