<?php
/*
 * by zhangshurui 20180119
 *
 * admin@hunnanren.com
 *
 * 微信公众号的access_token jsapi_ticket管理，支持多地服务器获取
 * 由于微信官方对access_token实行每日获取次数限制，重复获取，将导致前一次获取的token自动失效。
 * 因此对于程序有多处，或者服务器分布多个地方需要使用access_token,就需要实行统一管理。并且保证微信公众号的秘钥等信息只在一处保管。
 * 每次需要使用，调用此函数获取，不要自己保存。
 * 修改private $tokenPath="config/"; //token保存路径
 * 修改$auIp="127.0.0.1,101.200.197.176"; //允许访问此程序的服务器ip白名单。谨慎设置。
 * $this->config=array(
 *          "fuwu"=>array(
 *              "APPID"=>"wxa8fea24f06df4d20", //获取access_token,jsapi_ticket 必要条件
 *              "APPSECRET"=>"7ed49ec34d19fbf68664237f8835e4df", //获取access_token,jsapi_ticket 必要条件
 *          )
 *      );
 *修改函数curl中的默认根证书路径
 */


class weixinTokenManager{
    private $config;
    private $tokenPath="config/"; //token保存路径
    private $auIp="127.0.0.1,101.200.197.176"; //允许访问此程序的服务器ip白名单。谨慎设置。

    function __construct(){
        //$this->config=include "config/config.php"; //获取公众号配置文件
        $this->config=array(
            "fuwu"=>array(
                "APPID"=>"wxa8fea24f06df4d20", //获取access_token,jsapi_ticket 必要条件
                "APPSECRET"=>"7ed49ec34d19fbf68664237f8835e4df", //获取access_token,jsapi_ticket 必要条件
            )
        );

    }

    //获取access_token
    //AccountType 对应config配置
    //**//$location 是本地实例化类访问，还是提供外网获取接口 true/false,//**特别注意**//如果外网获取
    public function access_token($AccountType,$location=false){
        $ip=$this->ip();
        if($location==false and (!$ip[0])){
            return "如果是本地实例化此类，请设置location=true。如果是提供接口为其它服务器调用获取token，为了安全，location一定要设置为false，然后设置变量auip，设置调用服务器IP(".$ip[1].")为白名单。";
        }

        $access_token = @include $this->tokenPath.$AccountType.".access_token.php";
        if ($access_token['expire_time'] < time()) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->config[$AccountType]['APPID']."&secret=".$this->config[$AccountType]['APPSECRET'];
            $res = json_decode($this->curl($url),true);
            if(!isset($res['access_token'])){
                print_r($res);
                exit;
            }

            $access_token['access_token'] = $res['access_token'];
            $access_token['expire_time']=time() + 7000;

            if ($access_token) {
                file_put_contents($this->tokenPath.$AccountType.".access_token.php", "<?php return array('expire_time'=>".$access_token['expire_time'].",'access_token'=>'".$access_token['access_token']."');?>");
            }
        }
        return $access_token;

    }

    //获取jsapi_ticket
    //AccountType 对应config配置
    //$location 是本地实例化类访问，还是提供外网获取接口 true/false
    public function jsapi_ticket($AccountType,$location=false){
        $ip=$this->ip();
        if($location==false and (!$ip[0])){
            return "如果是本地实例化此类，请设置location=true。如果是提供接口为其它服务器调用获取token，为了安全，location一定要设置为false，然后设置变量auip，设置调用服务器IP(".$ip[1].")为白名单。";
        }

        $jsapi_ticket = @include $this->tokenPath.$AccountType.".jsapi_ticket.php";

        if ($jsapi_ticket['expire_time'] < time()) {
            $res = $this->access_token($AccountType,true);

            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$res['access_token'];
            $res = json_decode($this->curl($url),true);
            if(!isset($res['ticket'])){
                print_r($res);
                exit;
            }
            $jsapi_ticket['jsapi_ticket'] = $res['ticket'];
            $jsapi_ticket['expire_time']=time() + 7000;

            if ($jsapi_ticket) {
                file_put_contents($this->tokenPath.$AccountType.".jsapi_ticket.php", "<?php return array('expire_time'=>".$jsapi_ticket['expire_time'].",'jsapi_ticket'=>'".$jsapi_ticket['jsapi_ticket']."');?>");
            }
        }
        return $jsapi_ticket;


    }

    //获取并判断ip是否在白名单内
    private function ip(){
        $cip = "0.0.0.0";
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        }
        elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        elseif(!empty($_SERVER["REMOTE_ADDR"])){
            $cip = $_SERVER["REMOTE_ADDR"];
        }

        if(strpos($this->auIp,$cip)===false){
            return array(false,$cip);
        }
        return array(true,$cip);
    }

    //curl网络请求
    //url 请求地址
    //data post数据，如果为空，则使用get方式获取，如果不为空，则post方式传输data
    //verify 是否验证服务器证书
    //timeout 超时（秒），0不限
    //cainfo 签署服务器证书的权威机构的根证书保存路径，相对于当前文件位置，可以用来验证服务器证书的真实性，个人经验，大部分环境和工具未内置权威机构的根证书，所以直接都写上了。
    private function curl($url,$data="",$verify=true,$timeout=500,$cainfo="/cert/rootca.pem"){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//以文件流的形式返回
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

        if($verify){//是否验证访问的域名证书
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
            curl_setopt($curl,CURLOPT_CAINFO,dirname(__FILE__).$cainfo);
        }else{
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        if($data<>""){//data有内容，则通过post方式发送
            curl_setopt($curl,CURLOPT_POST,true);
            curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        if($res){
            curl_close($curl);
            return $res;
        } else {
            $error = curl_errno($curl);
            curl_close($curl);
            throw new Exception("curl出错，错误码:$error");
        }
    }

}
?>
<?php
/*示例*/
/*
include "wxTokenManager.class.php";
$wxTokenManager=new wxTokenManager();
echo "使用函数access_token，获取access_token：<br>";
$a=$wxTokenManager->access_token("fuwu",true);
print_r($a);
echo "<br><hr><br>";
echo "使用函数jsapi_ticket，获取jsapi_ticket：<br>";
$a=$wxTokenManager->jsapi_ticket("fuwu",true);
print_r($a);
 */
?>
