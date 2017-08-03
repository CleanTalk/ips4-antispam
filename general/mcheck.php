<?
$users = $_POST['users'];
$access_key = base64_decode($_POST['key']);
$data=Array();
$spam_array = array();
if (!empty($access_key) && isset ($users))
{
	foreach ($users as $key=>$value)
{
					$data[$key][]=$value['email'];
				$data[$key][]=$value['ip_address'];
}
for($i=0;$i<sizeof($data);$i++)
			    {
			    $error="";
				$send=implode(',',$data[$i]);;
				$req="data=$send";
				$opts = array(
				    'http'=>array(
				        'method'=>"POST",
				        'content'=>$req,
				    )
				);
				$context = stream_context_create($opts);
				$result = @file_get_contents("https://api.cleantalk.org/?method_name=spam_check_cms&auth_key=".$access_key, 0, $context);
				$result=json_decode($result);
				if(isset($result->error_message))
				{
					$error=$result->error_message;
				}
				else
				{
					if(isset($result->data))
					{
						foreach($result->data as $key=>$value)
						{
							if($key === filter_var($key, FILTER_VALIDATE_IP))
							{
								if($value->appears==1)
								{
									$spam_array[] = $users[$i];
								}
							}
							else
							{
								if($value->appears==1)
								{
									$spam_array[] = $users[$i];
								}
							}
						}
					}
				}
			}
if (count($spam_array)>0)
{
	$html='<div id ="caption_results"><center><h3>Done. All users checked via blacklists database, see result below.</h3></div><br /></center>';
$html.='<center><table id="table_results" style="border-color:#666666;" border=1 cellspacing=0 cellpadding=5>
	<thead>
	<col width="50">
   <col width="200" >
      <col width="150" >
         <col width="150" >
            <col width="150" >
               <col width="150" >
	<tr>
		<th>Select</th>
		<th>Username</th>
		<th>Registered</th>
		<th>E-mail</th>
		<th>IP</th>
		<th>Last visit</th>
	</tr>
	</thead>
	<tbody>';
	foreach ($spam_array as $key=>$value)
{
		$last_visit = (!empty($value['last_visit']))?date("Y-m-d H:i:s",$value['last_visit']):"<center>-</center>";	
		$html.="<tr>
		<td><center><input type='checkbox' name=ct_del_user[".$value['member_id']."] value='1' /></center></td>
		<td><center>".$value['name']."</center></td>
        <td><center>".date("Y-m-d H:i:s",$value['joined'])."</center></td>
        <td><center><a target='_blank' href='https://cleantalk.org/blacklists/".$value['email']."'><img src='https://cleantalk.org/images/icons/external_link.gif' border='0'/> ".$value['email']."</a></center></td>
        <td><center><a target='_blank' href='https://cleantalk.org/blacklists/".$value['ip_address']."'><img src='https://cleantalk.org/images/icons/external_link.gif' border='0'/> ".$value['ip_address']."</a></center></td>
        <td><center>".$last_visit."</center></td>
        </tr>";
}

$html.="</tbody></table></center><br/><center><b><a href=\"#\" id=\"ct_delete_checked\" onclick=\"var answer = confirm('Are you sure you want to delete these users? All messages and topics from this users also will be deleted!'); if (answer) $.ajax({type: 'POST', url: 'http://".$_SERVER['SERVER_NAME']."/uploads/updatedb.php', data: ''});\">Delete seleceted</a></b><span style='padding-left:10px;'> </span> <b><a href=\"#\" id=\"ct_delete_all\" onclick=\"var answer = confirm('Are you sure you want to delete these users? All messages and topics from this users also will be deleted!'); if (answer){ $.ajax({type: 'POST', url: 'http://".$_SERVER['SERVER_NAME']."/uploads/updatedb.php', data: '" . http_build_query(array('spam_users' => $spam_array)) . "', success: function(data){ $('#delete_results').html(data);}});}\">Delete all</a></b></center><br /><div id = 'delete_results'></div>";
}
else
{
	$html='<center><h3>No spam users found</h3><br /></center>';
}
}
else 
{
	$html='<center><h3>Access key error!</h3><br /></center>';
}
echo $html;
?>