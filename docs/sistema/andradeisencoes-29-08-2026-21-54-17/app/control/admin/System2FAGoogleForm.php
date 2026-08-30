<?php

use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;

/**
 * System2FAGoogleForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Matheus Agnes Dias
 * @copyright  https://www.madbuilder.com.br
 */
class System2FAGoogleForm extends TPage
{
    public function __construct()
    {
        try
        {
            parent::__construct();
            
            parent::setTargetContainer('adianti_right_panel');
            
            $html = new THtmlRenderer('app/resources/system_2fa_google_form.html');
            $html->disableHtmlConversion();
            $replaces = array();
        
            TTransaction::open('permission');
            
            $user = SystemUsers::newFromLogin(TSession::getValue('login'));
            $replaces = $user->toArray();

            $form = new TForm('System2FAGoogleForm');

            $btnVerify = new TButton('btn_two_factor');
            $btnVerify->setAction(new TAction(array('System2FAGoogleForm', 'onContinue')), _t('Verify and activate'));
            $btnVerify->addStyleClass(' btn btn-primary btn-block');

            $form->setFields([$btnVerify]);

            $replaces['btnVerify'] = $btnVerify;

            $googleAuthenticator = new GoogleAuthenticator();

            $ini  = AdiantiApplicationConfig::get();
            
            $replaces['secret'] = $googleAuthenticator->createSecret();
            $replaces['qrCode'] = $googleAuthenticator->getQRCode($user->email, $ini['general']['application'], $replaces['secret']);

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
            if(empty($param['google_code']))
            {
                throw new Exception(_t('Invalid verification code. Request a new code.'));
            }

            TTransaction::open('permission');
            
            $user = SystemUsers::newFromLogin(TSession::getValue('login'));
            
            if(GoogleAuthenticator::verifyCode($param['secret'], $param['google_code']))
            {
                $user->two_factor_enabled = 'Y';
                $user->two_factor_type = 'google_authenticator';
                $user->two_factor_secret = $param['secret'];
                $user->store();
            }

            TTransaction::close();

            TScript::create('Template.closeRightPanel();');
            new TMessage('info', _t('2FA by Google Authenticator successfully activated!'), new TAction(['System2FAForm', 'onShow']));
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
