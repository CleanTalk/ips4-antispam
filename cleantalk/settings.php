//<?php


$form->add( new \IPS\Helpers\Form\YesNo( 'plugin_enabled', \IPS\Settings::i()->plugin_enabled, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'plugin_enabled' ) ) );
$form->add( new \IPS\Helpers\Form\YesNo( 'moderate_new', \IPS\Settings::i()->moderate_new, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'moderate_new' ) ) );
$form->add( new \IPS\Helpers\Form\Text( 'access_key', \IPS\Settings::i()->access_key, FALSE, array( 'app' => 'core', 'key' => 'Admin', 'autoSaveKey' => 'access_key' ) ) );

if ( $values = $form->values() )
{
	$form->saveAsSettings();
	return TRUE;
}

return $form;