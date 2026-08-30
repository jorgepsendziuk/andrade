<?php

use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;

/**
 * SystemProfileView
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Matheus Agnes Dias
 * @copyright  https://www.madbuilder.com.br
 */
class System2FAForm extends TPage
{
    public function __construct()
    {
        try
        {
            parent::__construct();
            
            parent::setTargetContainer('adianti_right_panel');
            
            $html = new THtmlRenderer('app/resources/system_2fa_form.html');
            $replaces = array();
        
            TTransaction::open('permission');
            
            $user = SystemUsers::newFromLogin(TSession::getValue('login'));
            $replaces = $user->toArray();

            $form = new TForm('System2FAForm');

            $btnContinue = new TButton('btnContinue');
            $btnContinue->setAction(new TAction(array('System2FAForm', 'onContinue')), _t('Continue'));
            $btnContinue->addStyleClass(' btn btn-primary btn-block');

            $form->setFields([$btnContinue]);

            $replaces['btnContinue'] = $btnContinue;

            if($user->two_factor_enabled == 'Y' && $user->two_factor_type == 'email')
            {
                $html->enableSection('editEmail', []);
                $html->enableSection('edit', [
                    'displayGoogle' => "",
                    'displayEmail' => "display: none;",
                ]);
                
            }
            elseif($user->two_factor_enabled == 'Y' && $user->two_factor_type == 'google_authenticator')
            {
                $html->enableSection('editGoogle', []);
                $html->enableSection('edit', [
                    'displayGoogle' => "display: none;",
                    'displayEmail' => "",
                ]);
            }
            else
            {
                $html->enableSection('normal', []);    
            }
            TTransaction::close();
        
            $html->enableSection('main', $replaces);
            $html->enableTranslation();
            
            $container = TVBox::pack($html);
            $container->style = 'width: 100%';
            parent::add($container);

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onContinue($param)
    {
        try
        {
            if(empty($param['auth_method']))
            {
                throw new Exception(_t('You need to select a 2FA method'));
            }

            TTransaction::open('permission');
            $preferences = SystemPreference::getAllPreferences();
            TTransaction::close();
            
            if($param['auth_method'] == 'email' && (empty($preferences['2fa_by_email']) || $preferences['2fa_by_email'] == 'F'))
            {
                throw new Exception(_t('2FA by email is not enabled in the system'));
            }

            if($param['auth_method'] == 'google_authenticator' && (empty($preferences['2fa_by_google_auth']) || $preferences['2fa_by_google_auth'] == 'F'))
            {
                throw new Exception(_t('2FA by Google Aythenticator is not enabled in the system'));
            }

            if($param['auth_method'] == 'email')
            {   
                $form = new System2FAEmailForm([]);
                $form->setIsWrapped(true);
                $form->show();
            }
            elseif($param['auth_method'] == 'google_authenticator')
            {   
                $form = new System2FAGoogleForm([]);
                $form->setIsWrapped(true);
                $form->show();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onDisable2FA($param)
    {
        if(isset($param['disable']) && $param['disable'] == 1)
        {
            try
            {
                TTransaction::open('permission');

                $user = SystemUsers::newFromLogin(TSession::getValue('login'));

                $user->two_factor_enabled = 'N';
                $user->two_factor_type = '';
                $user->store();

                TTransaction::close();

                new TMessage('info', _t('2FA successfully disabled!'), new TAction(['System2FAForm', 'onShow']));
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage());   
            }
        }
        else
        {
            $action = new TAction(['System2FAForm', 'onDisable2FA']);
            $action->setParameters($param);
            $action->setParameter('disable', 1);

            new TQuestion(_t('Do you really want to disable 2FA ?'), $action);   
        }
    }

    public function onShow($param = null)
    {  

    }
}
