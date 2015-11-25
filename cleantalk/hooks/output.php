//<?php

class hook14 extends _HOOK_CLASS_
{
	public function getTitle( $title )
	{
		if(session_id()=='')session_start();
		$show_link=\IPS\Settings::i()->show_link;
		$html = '
<script type="text/javascript">
function ctSetCookie(c_name, value, def_value) {
    document.cookie = c_name + "=" + escape(value.replace(/^def_value$/, value)) + "; path=/";
}
ctSetCookie("%s", "%s", "%s");
</script>
';
		$ct_checkjs_key=md5(\IPS\Settings::i()->access_key . '+' . \IPS\Settings::i()->email_in . date("Ymd",time()));
		$html = sprintf($html, "ct_checkjs", $ct_checkjs_key, 0);
		if($show_link==1)
		{
			$html.="<div id='cleantalk_footer_link' style='width:100%;text-align:center;'><a href='https://cleantalk.org/ips-cs-4-anti-spam-plugin'>IPS spam</a> blocked by CleanTalk.</div>";
		}
		$this->endBodyCode.=$html;
		return $title;
	}
}