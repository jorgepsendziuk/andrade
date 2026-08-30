<?php
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class ApplicationAuthenticationService
{
    /**
     * Authenticate user and load permissions
     */
    public static function authenticate($login, $password, $load_session_vars = true)
    {
        $ini  = AdiantiApplicationConfig::get();
        
        $transactionClose = false;
        if(!TTransaction::isOpen('permission'))
        {
            $transactionClose = true;
            TTransaction::openFake('permission');
        }
        
        $user = SystemUsers::validate( $login );
        
        // call loaders to made available this attrs outside transactions
        $user->get_unit();
        $user->get_frontpage();
        
        if ($user)
        {
            if (!empty($ini['permission']['auth_service']) and class_exists($ini['permission']['auth_service']))
            {
                $service = $ini['permission']['auth_service'];
                $service::authenticate( $login, $password );
            }
            else
            {
                SystemUsers::authenticate( $login, $password );
            }
            
            if($load_session_vars)
            {
                self::loadSessionVars($user);
            }
            
            if ($transactionClose)
            {
                TTransaction::close();
            }
            return $user;
        }

        if ($transactionClose)
        {
            TTransaction::close();
        }
    }
    
    /**
     * Set Unit when multi unit is turned on
     * @param $unit_id Unit id
     */
    public static function setUnit($unit_id)
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['general']['multiunit']) && $ini['general']['multiunit'] == '1' && !empty($unit_id))
        {
            $transactionClose = false;
            if(!TTransaction::isOpen('permission'))
            {
                $transactionClose = true;
                TTransaction::openFake('permission');
            }
            
            $is_valid = in_array($unit_id, SystemUsers::newFromLogin( TSession::getValue('login') )->getSystemUserUnitIds());
            
            if (!$is_valid)
            {
                throw new Exception(_t('Unauthorized access to that unit'));
            }
            
            TSession::setValue('userunitid',   $unit_id );
            TSession::setValue('userunitname', SystemUnit::findInTransaction('permission', $unit_id)->name);
            
            if (!empty($ini['general']['multi_database']) and $ini['general']['multi_database'] == '1')
            {
                TSession::setValue('unit_database', SystemUnit::findInTransaction('permission', $unit_id)->connection_name );
            }

            if($transactionClose)
            {
                TTransaction::close();
            }
        }
    }
    
    /**
     * Set language when multi language is turned on
     * @param $lang_id Language id
     */
    public static function setLang($lang_id)
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['general']['multi_lang']) and $ini['general']['multi_lang'] == '1' and !empty($lang_id))
        {
            TSession::setValue('user_language', $lang_id );
        }
    }
    
    /**
     * Load user session variables
     */
    public static function loadSessionVars($user, $reloadunit = true)
    {
        $programs = $user->getPrograms();
        $programs['LoginForm'] = TRUE;
        
        TSession::setValue('logged', TRUE);
        TSession::setValue('login', $user->login);
        TSession::setValue('userid', $user->id);
        TSession::setValue('usergroupids', $user->getSystemUserGroupIds());
        TSession::setValue('userunitids', $user->getSystemUserUnitIds());
        TSession::setValue('username', $user->name);
        TSession::setValue('usermail', $user->email);
        TSession::setValue('frontpage', '');
        TSession::setValue('programs',$programs);
        TSession::setValue('programs_actions', $user->getProgramsActions());

        if (!empty($user->unit) && $reloadunit)
        {
            TSession::setValue('userunitid',$user->unit->id);
            TSession::setValue('userunitname', $user->unit->name);
        }
    }
    
    /**
     * Authenticate user from JWT token
     */
    public static function fromToken($token)
    {
        $ini = AdiantiApplicationConfig::get();
        $seed = APPLICATION_NAME . $ini['general']['seed'];
        
        if (empty($ini['general']['seed']))
        {
            throw new Exception('Application seed not defined');
        }
        
        $token = (array) JWT::decode($token, new Key($seed, 'HS256'));
        
        $login   = $token['user'];
        $userid  = $token['userid'];
        $name    = $token['username'];
        $email   = $token['usermail'];
        $expires = $token['expires'];
        
        if ($expires < strtotime('now'))
        {
            throw new Exception('Token expired. This operation is not allowed');
        }
        
        TSession::setValue('logged',   TRUE);
        TSession::setValue('login',    $login);
        TSession::setValue('userid',   $userid);
        TSession::setValue('username', $name);
        TSession::setValue('usermail', $email);
    }

    public static function clearSessions()
    {
        TTransaction::open('log');
        
        $logs = SystemAccessLog::where('login', '=', TSession::getValue('login'))->where('logout_time', 'IS', NULL)->load();
        
        if ($logs)
        {
            $session_save_path = session_save_path();
            $session_save_path = $session_save_path ? $session_save_path : sys_get_temp_dir();
                
            foreach($logs as $log)
            {
                if ($log->sessionid == session_id())
                {
                    continue;
                }
                
                $session_file = $session_save_path . "/sess_" . $log->sessionid;
                
                if (file_exists($session_file))
                {
                    unlink($session_file);
                }

                $log->logout_time = date('Y-m-d H:i:s');
                $log->store();
            }
        }
        
        TTransaction::close();
    }
}
