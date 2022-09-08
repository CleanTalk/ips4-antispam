//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class antispambycleantalk_hook_mcheck extends _HOOK_CLASS_
{
    protected function manage()
    {
        try
        {
            try
            {
                try
                {
                    /* Create the table */
                    $table = new \IPS\Helpers\Table\Db( 'core_members', \IPS\Http\Url::internal( 'app=core&module=members&controller=members' ), array( array( 'email<>?', 'members_bitoptions<>?', '', '65537') ), 'joined' );
                    if ($table->filter === 'members_filter_spam' && \IPS\Request::i()->ct_spam_check_run == 1)
                    {
                        if ( \IPS\Settings::i()->ct_spam_check && isset(\IPS\Settings::i()->ct_access_key) )
                        {
                            $on_page = 25;
                            $start = 0;
                            do
                            {
                                $select = \IPS\Db::i()->select( 'member_id,email,ip_address','core_members', null, null, array($start,$on_page));
                                $select = $select->setKeyField( 'member_id' );
                                $users = array();
                                $spam_users = array();
                                foreach( $select as $member_id => $value )
                                    $users[] = $value;
                                if (count($users)> 0)
                                {
                                    foreach ($users as $key=>$value)
                                    {
                                        $data[]=$value['email'];
                                        $data[]=$value['ip_address'];
                                    }

                                    $send=implode(',',array_unique($data));;
                                    $req="data=$send";
                                    $opts = array(
                                        'http'=>array(
                                            'method'=>"POST",
                                            'content'=>$req,
                                        )
                                    );

                                    $context = stream_context_create($opts);
                                    $result = @file_get_contents("https://api.cleantalk.org/?method_name=spam_check_cms&auth_key=".trim(\IPS\Settings::i()->ct_access_key), 0, $context);

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
														$spam_users['ip'][] = $key;
                                                    }
                                                }
                                                else
                                                {
                                                    if($value->appears==1)
                                                    {
														$spam_users['email'][] = $key;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                $start = $on_page + $start;
								if (!empty($spam_users['email']) || !empty($spam_users['ip'])) {
									$spam_users['email'] = is_array($spam_users['email']) ? array_unique($spam_users['email']) : array();
									$spam_users['ip'] = is_array($spam_users['ip']) ? array_unique($spam_users['ip']) : array();

									foreach ($users as $key=>$value)
									{
										if (
											(!empty($spam_users['email'] && in_array($value['email'], $spam_users['email'], true))) ||
											(!empty($spam_users['ip'] && in_array($value['ip_address'], $spam_users['ip'], true)))
										) {
											\IPS\Db::i()->update('core_members', array('members_bitoptions' => '65537'), array('member_id=?', $value['member_id']));
										}
									}
								}
                            }while (count($users) != 0);
                        }

                    }
                    parent::manage();
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
}
