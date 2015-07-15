//<?php

class hook12 extends _HOOK_CLASS_
{
	public function getTitle( $title )
	{
		$html = '
<script type="text/javascript">
function ctSetCookie(c_name, value, def_value) {
    document.cookie = c_name + "=" + escape(value.replace(/^def_value$/, value)) + "; path=/";
}
ctSetCookie("%s", "%s", "%s");
</script>
';
		$ct_checkjs_key=md5(\IPS\Settings::i()->access_key . '+' . \IPS\Settings::i()->email_in);
		$html = sprintf($html, "ct_checkjs", $ct_checkjs_key, 0);
		$this->endBodyCode.=$html;
		return $title;
	}
}