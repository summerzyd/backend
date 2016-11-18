<?php
namespace App\Components\Symbol;

class SymbolChild extends Symbol
{
    public function getUrl()
    {
        return '';
    }

    /**
     * 返回结果
     * @param $param
     * @return array
     */
    public function getValue($param)
    {
        $output = $this->getPostResult($param);
        if (empty($output)) {
            return null;
        }
        $ret = [];
        if (isset($output['data']['info'])) {
            $output = $output['data']['info'];
            foreach ($output as $data) {
                $ret[] = array(
                    'app_id' => $data['app_id'],
                    'app_name' => $data['name'],
                    'app_icon' => $data['icon'],
                    'app_href' => isset($data['app_info_url']) ? $data['app_info_url'] : '',
                    'app_vendor' => $data['vender']
                );
            }
        }
        return ['result' => 0, 'data' => $ret];
    }
}
