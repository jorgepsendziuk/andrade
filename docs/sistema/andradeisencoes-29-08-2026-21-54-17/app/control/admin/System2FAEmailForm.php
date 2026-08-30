<?php

use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;

/**
 * System2FAEmailForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Matheus Agnes Dias
 * @copyright  https://www.madbuilder.com.br
 */
class System2FAEmailForm extends TPage
{
    public function __construct()
    {
        parent::__construct();
        
        parent::setTargetContainer('adianti_right_panel');
        
        $html = new THtmlRenderer('app/resources/system_2fa_email_form.html');
        $replaces = array();
        
        try
        {
            TTransaction::open('permission');
            
            $user = SystemUsers::newFromLogin(TSession::getValue('login'));
            $replaces = $user->toArray();

            $form = new TForm('System2FAEmailForm');

            $btnVerify = new TButton('btn_two_factor');
            $btnVerify->setAction(new TAction(array('System2FAEmailForm', 'onContinue')), _t('Verify and activate'));
            $btnVerify->addStyleClass(' btn btn-primary btn-block');

            $form->setFields([$btnVerify]);

            $replaces['btnVerify'] = $btnVerify;

            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
        
        $html->enableSection('main', $replaces);
        $html->enableTranslation();
        
        $container = TVBox::pack($html);
        $container->style = 'width: 100%';
        parent::add($container);
    }

    public static function onContinue($param)
    {
        try
        {
            if(empty($param['email_code']))
            {
                throw new Exception(_t('Invalid verification code. Request a new code.'));
            }

            TTransaction::open('permission');
            
            $user = SystemUsers::newFromLogin(TSession::getValue('login'));
            
            if(TwoFactorEmailService::verifyEmailCode($param['email_code']))
            {
                $user->two_factor_enabled = 'Y';
                $user->two_factor_type = 'email';
                $user->store();
            }

            TTransaction::close();

            TScript::create('Template.closeRightPanel();');
            new TMessage('info', _t('2FA by email successfully activated!'), new TAction(['System2FAForm', 'onShow']));
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onSendCode($param)
    {
        try
        {
            TTransaction::open('permission');
            
            $user = SystemUsers::newFromLogin(TSession::getValue('login'));
            
            TwoFactorEmailService::generateAndSendEmailCode($user->email, $user->name);

            TTransaction::close();

            TScript::create("
                \$('#verificationStep').show();
                \$('#sendCodeBtn').html('"._t('Code sent')."').attr('disabled', 'disabled');
                setTimeout(function() { \$('#number1').focus(); },100);
            ");
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}
