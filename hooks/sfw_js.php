//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    exit;
}
require_once(\IPS\Application::getRootPath().'/applications/antispambycleantalk/sources/autoload.php');

use Cleantalk\ApbctIPS\RemoteCalls;
use Cleantalk\ApbctIPS\Cron;
use Cleantalk\Common\Firewall\Firewall;
use Cleantalk\ApbctIPS\DB;
use Cleantalk\Common\Variables\Server;
use Cleantalk\Common\Firewall\Modules\SFW;

class antispambycleantalk_hook_sfw_js extends _HOOK_CLASS_
{
    public function getTitle( $title ){
        try
        {
            try
            {

                try{
                    $this->apbct_run_cron();
                    if(
                        \IPS\Settings::i()->ct_cleantalk_sfw == 1 &&
                        !\IPS\Request::i()->isAjax()
                    )
                    {
                        $firewall = new Firewall(
                            \IPS\Settings::i()->ct_access_key,
                            DB::getInstance(),
                            APBCT_TBL_FIREWALL_LOG
                        );

                        $firewall->loadFwModule( new SFW(
                            APBCT_TBL_FIREWALL_DATA,
                            array(
                                'sfw_counter'   => 0,
                                'cookie_domain' => Server::get('HTTP_HOST'),
                                'set_cookies'    => 1,
                            )
                        ) );

                        $firewall->run();

                    }
                    // Remote calls
                    if( RemoteCalls::check() ) {
                        $rc = new RemoteCalls( \IPS\Settings::i()->ct_access_key );
                        $rc->perform();
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
    private function apbct_run_cron()
    {
        $cron = new Cron();
        $cron_name = $cron->getCronOptionName();
        if (!\IPS\Settings::i()->$cron_name) {
            $cron->addTask( 'sfw_update', 'apbct_sfw_update', 86400, time() + 60 );
            $cron->addTask( 'sfw_send_logs', 'apbct_sfw_send_logs', 3600 );
        }
        $tasks_to_run = $cron->checkTasks(); // Check for current tasks. Drop tasks inner counters.
        if(
            ! empty( $tasks_to_run ) && // There is tasks to run
            ! RemoteCalls::check() && // Do not doing CRON in remote call action
            (
                ! defined( 'DOING_CRON' ) ||
                ( defined( 'DOING_CRON' ) && DOING_CRON !== true )
            )
        ){
            $cron_res = $cron->runTasks( $tasks_to_run );
            // Handle the $cron_res for errors here.
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
}
