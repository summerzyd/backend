APP_ENV=local
APP_DEBUG=false
APP_KEY=Ly15XWKWf6MR5PDr29bb7RJWMhUyfA7I
APP_TIMEZONE=PRC
DB_TIMEZONE=+08:00
ORIGIN=http://localhost

DB_CONNECTION=mysql
DB_HOST=192.168.1.229
DB_PORT=3306
DB_DATABASE=dsp1224
DB_USERNAME=root
DB_PASSWORD=biddingos
DB_PREFIX=up_
AUTH_TABLE=up_users

MAIL_HOST=smtp.exmail.qq.com
MAIL_PORT=587
MAIL_FROM_ADDRESS=test@biddingos.com
MAIL_FROM_NAME=Leon
MAIL_USERNAME=test@biddingos.com
MAIL_PASSWORD=iwalnuts2015

NAV_FRONT_BASE=/bos-front/web

SESSION_DRIVER=memcached
MEMCACHED_HOST=127.0.0.1
MEMCACHED_PORT=11211
CACHE_DRIVER=memcached

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DATABASE=1
REDIS_PASSWORD=null

REDIS_DELIVERY_HOST=192.168.1.222
REDIS_DELIVERY_PORT=6379
REDIS_DELIVERY_DATABASE=0
REDIS_DELIVERY_PASSWORD=null

REDIS_PIKA_TARGET_HOST=10.45.147.125
REDIS_PIKA_TARGET_PORT=6380
REDIS_PIKA_TARGET_DATABASE=1
REDIS_PIKA_TARGET_PASSWORD=null

WORD2VEC_URL=http://10.173.34.71:4080/text2vec

REDIS_AD_SERVER_HOST=127.0.0.1
REDIS_AD_SERVER_PORT=6379
REDIS_AD_SERVER_DATABASE=0
REDIS_AD_SERVER_PASSWORD=null

QINIU_ACCESS_KEY=DXF60eoreRNsxkv0EoL9upcLJM6Fr61blIJiqnG0
QINIU_SECRET_KEY=v9_xUlzaGnePxrfGXW_HOAQylnTo0_knGBABmnwi
QINIU_BUCKET=test
QINIU_DOMAIN=http://7xnoye.com1.z0.glb.clouddn.com

UPLOAD_IMG_WEB=http://files.biddingos.com/
UPLOAD_FILE_WEB=http://files.biddingos.com/bos

YOUKU_ADX_AFID=108
YOUKU_ADX_DSPID=11217
YOUKU_ADX_TOKEN=5ce04a7e9cc34a87b57a1b37272c7f67
YOUKU_ADX_URL_PREFIX=http://miaozhen.atm.youku.com/dsp/api

YOUKU_VIDEO_UPLOAD_CLIENT_ID=cf7aa61114b3e323
YOUKU_VIDEO_UPLOAD_CLIENT_SECRET=c2f3132ca66f528b80d33ffda5fd03c9
YOUKU_VIDEO_UPLOAD_ACCESS_TOKEN=6ea3f0b706eb60213c83d8ea6424d39d
YOUKU_VIDEO_UPLOAD_REFRESH_TOKEN=695131022c7270edc0fcd367b9f0377a

IQIYI_ADX_AFID=101
IQIYI_ADX_TOKEN=b0e301668d864e2bb53c0d9c0d9beb07
IQIYI_ADX_URL_ADVERTISER_UPLOAD=http://220.181.184.220/upload/advertiser
IQIYI_ADX_URL_ADVERTISER_STATUS_SINGLE=http://220.181.184.220/upload/api/advertiser
IQIYI_ADX_URL_ADVERTISER_STATUS_MULTI=http://220.181.184.220/upload/api/batchAdvertiser
IQIYI_ADX_URL_AD_UPLOAD=http://220.181.184.220/upload/post
IQIYI_ADX_URL_AD_STATUS=http://220.181.184.220/upload/api/batchQuery

LETV_ADX_DSPID=22
LETV_ADX_TOKEN=sadfasdfsddfsdafsdfd
LETV_ADX_URL_ADVERTISER_UPLOAD=
LETV_ADX_URL_ADVERTISER_STATUS=
LETV_ADX_URL_AD_UPLOAD=http://ark.letv.com/apitest/ad/sync
LETV_ADX_URL_AD_STATUS=http://ark.letv.com/apitest/ad/getstatus

DAILY_MAIL_ADDRESS=shirleyxiao@lieying.cn;linpy@lieying.cn;jiazd@lieying.cn;panyj@lieying.cn;wuwei@lieying.cn;don@iwalnuts.com;sam@biddingos.com;operate@biddingos.com;sales@biddingos.com;meijie@biddingos.com;kerry@iwalnuts.com;simon@iwalnuts.com;shenjj@lieying.cn
ATTACH_BANNER_RELATION=maozhiming@iwalnuts.com;funson@iwalnuts.com;hexq@iwalnuts.com;zhangyoushu@iwalnuts.com;arke@iwalnuts.com

SOHU_AUTH_CONSUMER_KEY=''
SOHU_AUTH_CONSUMER_SECRET=''
SOHU_PRICE_KEY=''
V3_DOWNLOADCGI_URI='http://track.biddingos.com/trackserver/download'
V3_DOWNLOADENDCGI_URI='http://track.biddingos.com/trackserver/downloadend'
V3_CLICKCGI_URI='http://track.biddingos.com/trackserver/click'
V3_IMPRESSIONCGI_URI='http://track.biddingos.com/trackserver/impression'
LETV=http://123.125.91.30/mstore_api/mapi/cpd/list?wd=
IOS91=http://bbx2.sj.91.com/soft/phone/detail.aspx?act=226&mt=1&iv=7&identifier=

IMPRESSION_LIMIT=108
APPLE_ID_URL=https://itunes.apple.com/CN/lookup?id=
AFFILIATE_DOWNLOAD_COMPLETE = 117,134,135
