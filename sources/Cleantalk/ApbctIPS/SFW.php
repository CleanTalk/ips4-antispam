<?php

namespace Cleantalk\ApbctIPS;

use Cleantalk\Common\Variables\Cookie;

class SFW extends \Cleantalk\Common\Firewall\Modules\SFW {
	protected $test_status;
	public function check()
    {
		$results = array();
        $status = 0;

        if ( $this->test ) {
            unset($_COOKIE['ct_sfw_pass_key']);
            \Cleantalk\Common\Helper::apbct_cookie__set( 'ct_sfw_pass_key', '0', time() + 86400 * 3, '/', null, false, true, 'Lax' );
        }
		
		// Skip by cookie
		foreach( $this->ip_array as $current_ip ){

			if( substr( Cookie::get( 'ct_sfw_pass_key' ), 0, 32 ) == md5( $current_ip . $this->api_key ) ){

                if( Cookie::get( 'ct_sfw_passed' ) ) {
                    $results[] = array( 'ip' => $current_ip, 'is_personal' => false, 'status' => 'PASS_SFW__BY_COOKIE', );

                    // Do logging an one passed request
                    $this->update_log( $current_ip, 'PASS_SFW' );
                }

                if( strlen( Cookie::get( 'ct_sfw_pass_key' ) ) > 32 ) {
                    $status = substr( Cookie::get( 'ct_sfw_pass_key' ), -1 );
                }

                if( $status ) {
                    $results[] = array('ip' => $current_ip, 'is_personal' => false, 'status' => 'PASS_SFW__BY_WHITELIST',);
                }

                if(!empty($results)) {
					return $results;
				}
			}
		}
		
		// Common check
		foreach( $this->ip_array as $origin => $current_ip )
		{
			$current_ip_v4 = sprintf("%u", ip2long($current_ip));
			for ( $needles = array(), $m = 6; $m <= 32; $m ++ ) {
				$mask      = str_repeat( '1', $m );
				$mask      = str_pad( $mask, 32, '0' );
				$needles[] = sprintf( "%u", bindec( $mask & base_convert( $current_ip_v4, 10, 2 ) ) );
			}
			$needles = array_unique( $needles );
			
			$db_results = $this->db->fetch_all("SELECT
				network, mask, status
				FROM " . $this->db_data_table_name . "
				WHERE network IN (". implode( ',', $needles ) .")
				AND	network = " . $current_ip_v4 . " & mask 
				AND " . rand( 1, 100000 ) . "  
				ORDER BY status DESC");

            $test_status = 1;
			if( ! empty( $db_results ) ){
				
				foreach( $db_results as $db_result ){

					if( $db_result['status'] == 1 ) {
                        $results[] = array('ip' => $current_ip, 'is_personal' => false, 'status' => 'PASS_SFW__BY_WHITELIST',);
                        if ($this->test){
                            continue;
                        }
                        break;
                    }

					if ( $db_result['status'] == 0 ) {
                        $results[] = array('ip' => $current_ip, 'is_personal' => false, 'status' => 'DENY_SFW',);
                    }

                    $test_status = (int)$db_result['status'];
				}

			}else{
				
				$results[] = array( 'ip' => $current_ip, 'is_personal' => false, 'status' => 'PASS_SFW' );
				
			}
            if ( $this->test && $origin === 'sfw_test' ) {
                $this->test_status = $test_status;
            }
		}

		return $results;
	}
}