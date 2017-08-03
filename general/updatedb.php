<?
require_once '../init.php';
if (isset($_POST['spam_users']))
{
	$spam_users = $_POST['spam_users'];
	try
	{
		foreach ($spam_users as $key=>$value)
		{
			$delete = \IPS\Db::i()->delete( 'core_members',array( 'member_id=?', $value['member_id'] ) );
			$delete = \IPS\Db::i()->delete( 'forums_posts',array( 'author_id=?', $value['member_id'] ) );
			$delete = \IPS\Db::i()->delete( 'forums_topics',array( 'starter_id=?', $value['member_id'] ) );			
		}
		$html = '<script>var element = document.getElementById("table_results");
element.parentNode.removeChild(element);var element = document.getElementById("ct_delete_checked");
element.parentNode.removeChild(element);var element = document.getElementById("ct_delete_all");
element.parentNode.removeChild(element);var element = document.getElementById("caption_results");
element.parentNode.removeChild(element);var element = document.getElementById("check_spam");
element.parentNode.removeChild(element);</script><h4><center>Success!</center></h4>';
	}
	catch( \UnderflowException $e )
	{
	    $html = '<h4><center>Something went wrong!</center></h4>';
	}
	echo $html;
}

?>