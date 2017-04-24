<?php
	header('Content-type:text');
	define("TOKEN", "weixin");
	$wechatObj = new wechatCallbackapiTest();
	if (isset($_GET['echostr'])) {
	    $wechatObj->valid();
	}else{
	    $wechatObj->responseMsg();
	}

	class wechatCallbackapiTest{
	    public function valid(){
	        $echoStr = $_GET["echostr"];
	        if($this->checkSignature()){
	            echo $echoStr;
	            exit;
	        }
	    }

	    private function checkSignature(){
	        $signature = $_GET["signature"];
	        $timestamp = $_GET["timestamp"];
	        $nonce = $_GET["nonce"];

	        $token = TOKEN;
	        $tmpArr = array($token, $timestamp, $nonce)
	        sort($tmpArr);
	        $tmpStr = implode( $tmpArr );
	        $tmpStr = sha1( $tmpStr );

	        if( $tmpStr == $signature ){
	            return true;
	        }else{
	            return false;
	        }
	    }

	    public function responseMsg(){
	        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
	        if (!empty($postStr)){
	            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
	            $RX_TYPE = strtolower($postObj->MsgType);

	            switch ($RX_TYPE)
	            {
	                case "text":
	                    $resultStr = $this->receiveText($postObj);
	                    break;
	                case 'event':
	                	$resultStr = $this->receiveEvent($postObj);
	                	break;
	            }
	            echo $resultStr;
	        }else {
	            echo "";
	            exit;
	        }
	    }

	    private function receiveText($object){
	        $funcFlag = 0;
	        $keyword = trim($object->Content);
	        $resultStr = "";
	        $contentStr = "";

	        if($keyword == "文本"){
	            $contentStr = "这是个文本消息";
	            $resultStr = $this->transmitText($object, $contentStr, $funcFlag);
	        }
	        else if($keyword == "图文" || $keyword == "单图文"){
	            $dateArray = array();
	            $dateArray[] = array("Title"=>"单图文标题", 
	                                "Description"=>"单图文内容", 
	                                "Picurl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", 
	                                "Url" =>"http://m.cnblogs.com/?u=txw1958");
	            $resultStr = $this->transmitNews($object, $dateArray, $funcFlag);
	        }
	        else if($keyword == "多图文"){
	            $dateArray = array();
	            $dateArray[] = array("Title"=>"多图文1标题", "Description"=>"", "Picurl"=>"http://discuz.comli.com/weixin/weather/icon/cartoon.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
	            $dateArray[] = array("Title"=>"多图文2标题", "Description"=>"", "Picurl"=>"http://d.hiphotos.bdimg.com/wisegame/pic/item/f3529822720e0cf3ac9f1ada0846f21fbe09aaa3.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
	            $dateArray[] = array("Title"=>"多图文3标题", "Description"=>"", "Picurl"=>"http://g.hiphotos.bdimg.com/wisegame/pic/item/18cb0a46f21fbe090d338acc6a600c338644adfd.jpg", "Url" =>"http://m.cnblogs.com/?u=txw1958");
	            $resultStr = $this->transmitNews($object, $dateArray, $funcFlag);
	        }
	        else if($keyword == "音乐"){
	            $musicArray = array("Title"=>"最炫民族风", "Description"=>"歌手：凤凰传奇", "MusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3","HQMusicUrl"=>"http://121.199.4.61/music/zxmzf.mp3");
	            $resultStr = $this->transmitMusic($object, $musicArray, $funcFlag);
	        }
	        return $resultStr;
	    }

	    private function receiveEvent($object){
	        $contentStr = "";
	        switch (strtolower($object->Event))
	        {
	            case "subscribe":
	                $contentStr = "欢迎关注";
	                $resultStr = $this->transmitText($object, $contentStr);
	                break;  
	            case "click":
	            	switch ($object->EventKey) {
	            	 	case 'recommend':
	            	 		$contentStr = "图书推荐";
	            	 		$resultStr = $this->transmitText($object, $contentStr);
	            	 		break;
	            	 	case 'code':
	            	 		$picUrl=$this->getQrCode();
	            	 		$arr_item=array(
	            	 			array(
	            	 				'Title' =>'我的借书二维码',
		            	 			'Description'=>'出示你的借书二维码给管理员',
		            	 			'Picurl'=>$picUrl,
		            	 			'Url'=>$picUrl
	            	 				)	            	 			
	            	 		);
	            	 		$resultStr=$this->transmitNews($object,$arr_item);
	            	 		break;
	            	 	case 'binding':
	            	 		$contentStr = "点击绑定：http://1.thebooks.applinzi.com/test.php";
	            	 		$resultStr = $this->transmitText($object, $contentStr);
	            	 		break;
	            	 	default:
	            	 		# code...
	            	 		break;
	            	 } 
	            	break;
	        }
	        return $resultStr;
	    }

	    private function transmitText($object, $content, $flag = 0){
	        $textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[text]]></MsgType>
			<Content><![CDATA[%s]]></Content>
			<FuncFlag>%d</FuncFlag>
			</xml>";
	        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $content, $flag);
	        return $resultStr;
	    }

	    private function transmitNews($object, $arr_item, $flag = 0){
	        if(!is_array($arr_item))
	            return;

	        $itemTpl = "    <item>
		        <Title><![CDATA[%s]]></Title>
		        <Description><![CDATA[%s]]></Description>
		        <PicUrl><![CDATA[%s]]></PicUrl>
		        <Url><![CDATA[%s]]></Url>
	    	</item>";
	        $item_str = "";
	        foreach ($arr_item as $item)
	            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['Picurl'], $item['Url']);

	        $newsTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[news]]></MsgType>
			<ArticleCount>%s</ArticleCount>
			<Articles>
			$item_str</Articles>
			<FuncFlag>%s</FuncFlag>
			</xml>";

	        $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, time(), count($arr_item), $flag);
	        return $resultStr;
	    }
	    
	    private function transmitMusic($object, $musicArray, $flag = 0){
	        $itemTpl = "<Music>
		    <Title><![CDATA[%s]]></Title>
		    <Description><![CDATA[%s]]></Description>
		    <MusicUrl><![CDATA[%s]]></MusicUrl>
		    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
			</Music>";

	        $item_str = sprintf($itemTpl, $musicArray['Title'], $musicArray['Description'], $musicArray['MusicUrl'], $musicArray['HQMusicUrl']);

	        $textTpl = "<xml>
			<ToUserName><![CDATA[%s]]></ToUserName>
			<FromUserName><![CDATA[%s]]></FromUserName>
			<CreateTime>%s</CreateTime>
			<MsgType><![CDATA[music]]></MsgType>
			$item_str
			<FuncFlag>%d</FuncFlag>
			</xml>";

	        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, time(), $flag);
	        return $resultStr;
	    }

	    private function getWxAccessToken($appId,$appSecret){
			$url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$appSecret";
			$ch=curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
			$res=curl_exec($ch);
			if (curl_errno($ch)) {
				var_dump(curl_errno($ch));
			}
			curl_close($ch);
			$jsonInfo=json_decode($res,true);
			$access_token=$jsonInfo["access_token"];
			return $access_token;
		}

		private function https_curl($url,$data=null){
		    $curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, $url);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		    if (!empty($data)){
		        curl_setopt($curl, CURLOPT_POST, 1);
		        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		    }
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    $output = curl_exec($curl);
		    curl_close($curl);
		    return $output;
		}
		//获取二维码
		private function getQrCode(){
			$appId='wxc6d5e6987c67fe47';
			$appSecret='064e084bc610ee2fb9459d99a744e6d3';
			$access_token=$this->getWxAccessToken($appId,$appSecret);
			$url= "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=$access_token";
			$qrcode='{"expire_seconds": 60, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 100}}}';
			$result=$this->https_curl($url,$qrcode);
			$jsoninfo=json_decode($result,true);
			$ticket=$jsoninfo['ticket'];
			$url="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;
			return $url;
		}
	}
?>