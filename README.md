 * by zhangshurui 20180119
 *
 * admin@hunnanren.com
 *
 * 微信公众号的access_token jsapi_ticket管理，支持多地服务器获取
 * 由于微信官方对access_token实行每日获取次数限制，重复获取，将导致前一次获取的token自动失效。
 * 因此对于程序有多处，或者服务器分布多个地方需要使用access_token,就需要实行统一管理。并且保证微信公众号的秘钥等信息只在一处保管。
 ***********************每次需要使用，调用此函数获取，不要自己保存***********************************************
 *
 *
 * 修改private $tokenPath="config/"; //token保存路径
 * 修改$auIp="127.0.0.1,101.200.197.176"; //允许访问此程序的服务器ip白名单。谨慎设置。
 * $this->config=array(
 *          "fuwu"=>array(
 *              "APPID"=>"wxa8fea24f06df4d20", //获取access_token,jsapi_ticket 必要条件
 *              "APPSECRET"=>"7ed49ec34d19fbf68664237f8835e4df", //获取access_token,jsapi_ticket 必要条件
 *          )
 *      );
 *修改函数curl中的默认根证书路径
 *
 *
 *
 *微信公众号的access_token jsapi_ticket管理，支持多地服务器获取：https://github.com/juminut/weixinTokenManager
 *
 *微信登录，获取用户资料信息，类代码：https://github.com/juminut/weixinOauth2
 *
 *