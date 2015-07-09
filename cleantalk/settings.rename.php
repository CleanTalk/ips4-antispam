//<?php

$form->add( new \IPS\Helpers\Form\Text( 'plugin_example_setting', \IPS\Settings::i()->plugin_example_setting ) );

if ( $values = $form->values() )
{
	$form->saveAsSettings();
	return TRUE;
}

return $form;