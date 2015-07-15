//<?php


/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Install Code
 */
class ips_plugins_setup_install
{
	/**
	 * ...
	 *
	 * @return	array	If returns TRUE, upgrader will proceed to next step. If it returns any other value, it will set this as the value of the 'extra' GET parameter and rerun this step (useful for loops)
	 */
	public function step1()
	{
		$file = file_get_contents('https://raw.githubusercontent.com/CleanTalk/php-antispam/master/cleantalk.class.php');
		if($file === FALSE)
		{
	    	return FALSE;
		}
		if(file_put_contents(dirname($_SERVER['SCRIPT_FILENAME'])."/../uploads/cleantalk.class.php" , $file) === FALSE)
		{
		    return FALSE;
		}
		
		$file = file_get_contents('https://raw.githubusercontent.com/CleanTalk/php-antispam/master/JSON.php');
		if($file === FALSE)
		{
	    	return FALSE;
		}
		if(file_put_contents(dirname($_SERVER['SCRIPT_FILENAME'])."/../uploads/JSON.php" , $file) === FALSE)
		{
		    return FALSE;
		}
		
		
		\IPS\Db::i()->query( "CREATE TABLE IF NOT EXISTS cleantalk_timelabels (ct_key varchar(255), ct_value int(11), PRIMARY KEY (ct_key) ) ENGINE=myisam" );
	    \IPS\Db::i()->query( "CREATE TABLE IF NOT EXISTS cleantalk_server (work_url varchar(255), server_ttl int(11), server_changed int(11) ) ENGINE=myisam" );
	    \IPS\Db::i()->query( "CREATE TABLE IF NOT EXISTS cleantalk_settings (ct_key varchar(255), ct_value varchar(255), PRIMARY KEY (ct_key) ) ENGINE=myisam" );

		return TRUE;
	}
	
	// You can create as many additional methods (step2, step3, etc.) as is necessary.
	// Each step will be executed in a new HTTP request
}