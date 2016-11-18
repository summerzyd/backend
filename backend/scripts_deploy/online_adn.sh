#!/bin/bash
echo "$(date +%F-%T) 开始 替换配置文件为adn  配置文件"
cd /data/adn/bos-backend-api/
pwd

#sed -i "s#UPLOAD_IMG_WEB=http://files.biddingos.com/#UPLOAD_IMG_WEB=http://sxtfiles.biddingos.com/#"  /sx_allfile/adn-sxt/bos-backend-api/.env
#sed -i "s#MEMCACHED_HOST=\(.*\)#MEMCACHED_HOST=10.51.116.168#" /sx_allfile/jzdir/adn-sxt/bos-backend-api/.env
#sed -i "s#REDIS_HOST=\(.*\)#REDIS_HOST=10.174.92.172#"  /sx_allfile/jzdir/adn-sxt/bos-backend-api/.env
#sed -i "s#REDIS_PORT=\(.*\)#REDIS_PORT=6379#" /sx_allfile/jzdir/adn-sxt/bos-backend-api/.env
#sed -i "s#REDIS_DATABASE=\(.*\)#REDIS_DATABASE=0#" /sx_allfile/jzdir/adn-sxt/bos-backend-api/.env
#sed -i "s#REDIS_PASSWORD=\(.*\)#REDIS_PASSWORD=#" /sx_allfile/jzdir/adn-sxt/bos-backend-api/.env

rm -f .env

cd /data/adn/bos-backend-api/

sxt_dev_cfg='/data/adn/bos-backend-api/.env.dsp.biddingos.com'
sxt_cfg='/srv/adn-config/.env.dsp.biddingos.com'

sed -i 's/\ //g' ${sxt_dev_cfg}
sed -i 's/\ //g' ${sxt_cfg}



for line in `cat ${sxt_cfg}` 
#grep -v ^$ ${adn_cfg} | while read line
do
       list=`echo "$line" | awk -F "=" '{print $1}' `

       sxt_cfg_sx=`egrep -w ${list}.* ${sxt_cfg}`
       
       sed -i "s#${list}\(.*\)#${sxt_cfg_sx}#"  ${sxt_dev_cfg}
                  if [ $? -eq 0 ];then                       
			echo "$(date +%F-%T) 替换sed-${list}\= 成功ok "
             else  
                       echo "$(date +%F-%T) 替换sed-${list}\=失败 error exiting..."  && exit
              fi
done 



for config in ".env.www.pinxiaotong.com"  ".env.ssp.biddingos.com"  ".env.pinxiaotong.com"  ".env" 
do

\cp -af  /data/adn/bos-backend-api/.env.dsp.biddingos.com  /data/adn/bos-backend-api/${config}

if [ $? -eq "0" ];then
        echo "$(date +%F-%T) 复制adn bos-backend api 配置  文件成功"                                                                                                                                        
else
        echo "$(date +%F-%T) 复制adn bos-backend api 配置   文件失败 error exiting...." && exit
fi

done
