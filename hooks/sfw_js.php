//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class antispambycleantalk_hook_sfw_js extends _HOOK_CLASS_
{
    public function getTitle( $title ){
        try
        {
            try
            {

                try{

                    if ( method_exists( '\IPS\Application', "getRootPath" ) ) {
                        $sfw_file_path = \IPS\Application::getRootPath()."/applications/antispambycleantalk/sources/Cleantalk/cleantalk-sfw.class.php";
                    } else {
                        // old IPS support
                        $sfw_file_path = \IPS\ROOT_PATH."/applications/antispambycleantalk/sources/Cleantalk/cleantalk-sfw.class.php";
                    }

                    if(
                        \IPS\Settings::i()->ct_cleantalk_sfw == 1 &&
                        file_exists( $sfw_file_path ) &&
                        \IPS\Db::i()->checkForTable('antispambycleantalk_sfw')
                    )
                    {

                        $is_sfw_check=true;
                        $ip=$this->CleantalkGetIP();
                        $ip=array_unique($ip);
                        $key=\IPS\Settings::i()->ct_access_key;
                        for($i=0;$i<sizeof($ip);$i++){

                            if(isset($_COOKIE['ct_sfw_pass_key']) && $_COOKIE['ct_sfw_pass_key']==md5($ip[$i].$key)){

                                $is_sfw_check=false;

                                if(isset($_COOKIE['ct_sfw_passed']))
                                    @setcookie ('ct_sfw_passed', '0', 1, "/");
                            }
                        }
                        if($is_sfw_check){

                            require_once( $sfw_file_path );
                            $sfw = new \CleanTalkSFW();
                            $sfw->cleantalk_get_real_ip();
                            $sfw->check_ip();
                            if($sfw->result) {
                                $sfw->sfw_die();
                            }

                        }
                    }


                    $ct_show_link=\IPS\Settings::i()->ct_show_link;
                    $html = '
									<script type="text/javascript">
										function ctSetCookie(c_name, value) {
											document.cookie = c_name + "=" + encodeURIComponent(value) + "; path=/";
										}
		
										ctSetCookie("ct_ps_timestamp", Math.floor(new Date().getTime()/1000));
										ctSetCookie("ct_fkp_timestamp", "0");
										ctSetCookie("ct_pointer_data", "0");
										ctSetCookie("ct_timezone", "0");
		
										setTimeout(function(){
											ctSetCookie("ct_checkjs", "%s");
											ctSetCookie("ct_timezone", d.getTimezoneOffset()/60*(-1));
										},1000);
		
								//Stop observing function
										function ctMouseStopData(){
											if(typeof window.addEventListener == "function")
												window.removeEventListener("mousemove", ctFunctionMouseMove);
											else
												window.detachEvent("onmousemove", ctFunctionMouseMove);
											clearInterval(ctMouseReadInterval);
											clearInterval(ctMouseWriteDataInterval);				
										}
		
								//Stop key listening function
										function ctKeyStopStopListening(){
											if(typeof window.addEventListener == "function"){
												window.removeEventListener("mousedown", ctFunctionFirstKey);
												window.removeEventListener("keydown", ctFunctionFirstKey);
											}else{
												window.detachEvent("mousedown", ctFunctionFirstKey);
												window.detachEvent("keydown", ctFunctionFirstKey);
											}
											clearInterval(ctMouseReadInterval);
											clearInterval(ctMouseWriteDataInterval);				
										}
		
										var d = new Date(), 
											ctTimeMs = new Date().getTime(),
											ctMouseEventTimerFlag = true, //Reading interval flag
											ctMouseData = "[",
											ctMouseDataCounter = 0;
											
								//Reading interval
										var ctMouseReadInterval = setInterval(function(){
												ctMouseEventTimerFlag = true;
											}, 300);
											
								//Writting interval
										var ctMouseWriteDataInterval = setInterval(function(){ 
												var ctMouseDataToSend = ctMouseData.slice(0,-1).concat("]");
												ctSetCookie("ct_pointer_data", ctMouseDataToSend);
											}, 1200);
		
								//Logging mouse position each 300 ms
										var ctFunctionMouseMove = function output(event){
											if(ctMouseEventTimerFlag == true){
												var mouseDate = new Date();
												ctMouseData += "[" + event.pageY + "," + event.pageX + "," + (mouseDate.getTime() - ctTimeMs) + "],";
												ctMouseDataCounter++;
												ctMouseEventTimerFlag = false;
												if(ctMouseDataCounter >= 100)
													ctMouseStopData();
											}
										}
								//Writing first key press timestamp
										var ctFunctionFirstKey = function output(event){
											var KeyTimestamp = Math.floor(new Date().getTime()/1000);
											ctSetCookie("ct_fkp_timestamp", KeyTimestamp);
											ctKeyStopStopListening();
										}
		
										if(typeof window.addEventListener == "function"){
											window.addEventListener("mousemove", ctFunctionMouseMove);
											window.addEventListener("mousedown", ctFunctionFirstKey);
											window.addEventListener("keydown", ctFunctionFirstKey);
										}else{
											window.attachEvent("onmousemove", ctFunctionMouseMove);
											window.attachEvent("mousedown", ctFunctionFirstKey);
											window.attachEvent("keydown", ctFunctionFirstKey);
										}
									</script>';
                    $ct_checkjs_key = md5(\IPS\Settings::i()->ct_access_key . '+' . \IPS\Settings::i()->email_in . date("Ymd",time()));
                    $html = sprintf($html, $ct_checkjs_key);
                    if( $ct_show_link == 1 )
                        $html.="<div id='cleantalk_footer_link' style='width:100%;text-align:center;'><a href='https://cleantalk.org/ips-cs-4-anti-spam-plugin'>IPS spam</a> blocked by CleanTalk.</div>";
                    $this->setCookie();
                    $this->endBodyCode.=$html;
                    return $title;
                }
                catch ( \RuntimeException $e ){

                    if ( method_exists( get_parent_class(), __FUNCTION__ ) )
                        return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
                    else
                        throw $e;
                }
            }
            catch ( \RuntimeException $e )
            {
                if ( method_exists( get_parent_class(), __FUNCTION__ ) )
                {
                    return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
                }
                else
                {
                    throw $e;
                }
            }
        }
        catch ( \RuntimeException $e )
        {
            if ( method_exists( get_parent_class(), __FUNCTION__ ) )
            {
                return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
            }
            else
            {
                throw $e;
            }
        }
    }
    public function setCookie()
    {
        try
        {
            try
            {
                // Cookie names to validate
                $cookie_test_value = array(
                    'cookies_names' => array(),
                    'check_value' => trim(\IPS\Settings::i()->ct_access_key),
                );
                // Pervious referer
                if(!empty($_SERVER['HTTP_REFERER'])){
                    setcookie('ct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
                    $cookie_test_value['cookies_names'][] = 'ct_prev_referer';
                    $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
                }

                // Cookies test
                $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
                setcookie('ct_cookies_test', json_encode($cookie_test_value), 0, '/');
            }
            catch ( \RuntimeException $e )
            {
                if ( method_exists( get_parent_class(), __FUNCTION__ ) )
                {
                    return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
                }
                else
                {
                    throw $e;
                }
            }
        }
        catch ( \RuntimeException $e )
        {
            if ( method_exists( get_parent_class(), __FUNCTION__ ) )
            {
                return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
            }
            else
            {
                throw $e;
            }
        }
    }
    public function CleantalkGetIP(){
        try
        {
            try
            {
                $result=Array();

                if ( \function_exists( 'apache_request_headers' ) )
                    $headers = apache_request_headers();
                else
                    $headers = $_SERVER;

                if ( array_key_exists( 'X-Forwarded-For', $headers ) ){
                    $the_ip=explode(",", trim($headers['X-Forwarded-For']));
                    $result[] = trim($the_ip[0]);
                }

                if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers )){
                    $the_ip=explode(",", trim($headers['HTTP_X_FORWARDED_FOR']));
                    $result[] = trim($the_ip[0]);
                }

                $result[] = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );

                if(isset($_GET['sfw_test_ip']))
                    $result[]=$_GET['sfw_test_ip'];

                return $result;
            }
            catch ( \RuntimeException $e )
            {
                if ( method_exists( get_parent_class(), __FUNCTION__ ) )
                {
                    return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
                }
                else
                {
                    throw $e;
                }
            }
        }
        catch ( \RuntimeException $e )
        {
            if ( method_exists( get_parent_class(), __FUNCTION__ ) )
            {
                return \call_user_func_array( 'parent::' . __FUNCTION__, \func_get_args() );
            }
            else
            {
                throw $e;
            }
        }
    }
}
