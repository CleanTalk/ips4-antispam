<?php

namespace Cleantalk\Common\Variables;

/**
 * Class Cookie
 * Safety handler for $_COOKIE
 *
 * @since 3.0
 * @package Cleantalk\Variables
 */
class Cookie extends SuperGlobalVariables{
	
	static $instance;
	
	/**
	 * Gets given $_COOKIE variable and save it to memory
	 * @param $name
	 *
	 * @return mixed|string
	 */
	protected function get_variable( $name, $do_decode = true ){
		
		// Return from memory. From $this->variables
		if(isset(static::$instance->variables[$name]))
			return static::$instance->variables[$name];

        if( function_exists( 'filter_input' ) ){
            if ( isset($_COOKIE[ $name ])){
                $value = filter_input( INPUT_COOKIE, $name );
            }
        }

		if( empty( $value ) )
			$value = isset( $_COOKIE[ $name ] ) ? $_COOKIE[ $name ]	: '';
		
		$value = $do_decode ? urldecode( $value ) : $value;
		
		return $value;
	}
}