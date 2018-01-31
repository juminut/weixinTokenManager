<?php
include "weixinTokenManager.class.php";
$wxTokenManager=new weixinTokenManager();
echo "使用函数access_token，获取access_token：<br>";
$a=$wxTokenManager->access_token("fuwu",true);
print_r($a);
echo "<br><hr><br>";

echo "使用函数jsapi_ticket，获取jsapi_ticket：<br>";
$a=$wxTokenManager->jsapi_ticket("fuwu",true);
print_r($a);
?>