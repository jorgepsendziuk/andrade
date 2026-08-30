<?php

use Adianti\Widget\Form\TCheckButton;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THtmlEditor;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Wrapper\TDBSelect;

/**
 * SystemPreferenceForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemPreferenceForm extends TStandardForm
{
    protected $form; // formulário
    
    /**
     * método construtor
     * Cria a página e o formulário de cadastro
     */
    function __construct()
    {
        parent::__construct();
        
        $this->setDatabase('permission');
        $this->setActiveRecord('SystemPreference');
        
        // cria o formulário
        $this->form = new BootstrapFormBuilder('form_preferences');
        $this->form->setFormTitle(_t('Preferences'));
        
        // cria os campos do formulário
        $smtp_auth   = new TCombo('smtp_auth');
        $smtp_host   = new TEntry('smtp_host');
        $smtp_port   = new TEntry('smtp_port');
        $smtp_user   = new TEntry('smtp_user');
        $smtp_pass   = new TPassword('smtp_pass');
        $mail_from   = new TEntry('mail_from');
        $mail_support= new TEntry('mail_support');
        $term_policy = new THtmlEditor('term_policy');
        $strong_passowrd = new TCheckButton('strong_password');
        $maintenance_enabled = new TCheckButton('maintenance_enabled');
        $maintenance_message = new TText('maintenance_message');
        $maintenance_users = new TDBMultiSearch('maintenance_users', 'permission', 'SystemUsers', 'id', 'name','name asc');
        $google_recaptcha_site_key   = new TPassword('google_recaptcha_site_key');
        $google_recaptcha_secret_key   = new TPassword('google_recaptcha_secret_key');
        $google_recaptcha = new TCheckButton('google_recaptcha');
        $e2fa_by_email = new TCheckButton('2fa_by_email');
        $e2fa_by_google_auth = new TCheckButton('2fa_by_google_auth');
        $fa_email_subject = new TEntry('2fa_email_subject');
        $fa_email_content = new THtmlEditor('2fa_email_content');
        $firebase_json = new TText('firebase_json');
        $firebase_config = new TText('firebase_config');
        $internal_chat = new TCheckButton('internal_chat');
        $internal_chat_unauthorized_groups = new TDBMultiSearch('internal_chat_unauthorized_groups', 'permission', 'SystemGroup', 'id', 'name','name asc');
        $internal_chat_unauthorized_users = new TDBMultiSearch('internal_chat_unauthorized_users', 'permission', 'SystemUsers', 'id', 'name','name asc');
        $single_tab_mode = new TCheckButton('single_tab_mode');


        $internal_chat_unauthorized_users->setFilterColumns(["email","login","name"]);
        $maintenance_users->setFilterColumns(["email","login","name"]);

        $internal_chat_unauthorized_users->setMinLength(0);
        $internal_chat_unauthorized_groups->setMinLength(0);
        $maintenance_users->setMinLength(0);

        $internal_chat_unauthorized_groups->setMask('{name}');
        $internal_chat_unauthorized_users->setMask('{name} ({login})');
        $maintenance_users->setMask('{name} ({login})');

        $smtp_host->placeholder = 'ssl://smtp.gmail.com, tls://server.company.com';
        
        $yesno = array();
        $yesno['1'] = _t('Yes');
        $yesno['0'] = _t('No');
        $smtp_auth->addItems($yesno);

        $e2fa_by_email->setIndexValue('T');
        $e2fa_by_email->setInactiveIndexValue('F');
        $e2fa_by_email->setUseSwitch();
        $e2fa_by_email->enableCardLayout(_t('Enable 2FA by email'), _t('When enabled, users will be able to use 2FA by email'));

        $e2fa_by_google_auth->setIndexValue('T');
        $e2fa_by_google_auth->setInactiveIndexValue('F');
        $e2fa_by_google_auth->setUseSwitch();
        $e2fa_by_google_auth->enableCardLayout(_t('Enable 2FA by Google Authenticator'), _t('When enabled, users will be able to use 2FA by Google Authenticator'));

        $strong_passowrd->setIndexValue('T');
        $strong_passowrd->setInactiveIndexValue('F');
        $strong_passowrd->setUseSwitch();
        $strong_passowrd->enableCardLayout(_t('Strong password'), _t('Requires that the user password contains 8 characters, including numbers, uppercase letters, lowercase letters, and special characters'));

        $maintenance_enabled->setIndexValue('T');
        $maintenance_enabled->setInactiveIndexValue('F');
        $maintenance_enabled->setUseSwitch();
        $maintenance_enabled->enableCardLayout(_t('Maintenance mode'), _t('When enabled, only authorized users will be able to access the system'));

        $google_recaptcha->setIndexValue('T');
        $google_recaptcha->setInactiveIndexValue('F');
        $google_recaptcha->setUseSwitch();
        $google_recaptcha->enableCardLayout('Google reCAPTCHA', _t('When enabled, adds reCAPTCHA verification to the login screen for protection against bots'));

        $internal_chat->setIndexValue('T');
        $internal_chat->setInactiveIndexValue('F');
        $internal_chat->setUseSwitch();
        $internal_chat->enableCardLayout(_t('Internal chat'), _t('Allows system users to chat with each other or in groups').'<br>'. '<small>'. _t('To use the internal chat, it is necessary to have provided the Firebase JSON configuration.').'</small>');

        $single_tab_mode->setIndexValue('T');
        $single_tab_mode->setInactiveIndexValue('F');
        $single_tab_mode->setUseSwitch();
        $single_tab_mode->enableCardLayout('Modo de aba única', 'Restringe o uso do sistema a apenas uma aba do navegador');

        $google_recaptcha_site_key->setEditable(false);
        $google_recaptcha_secret_key->setEditable(false);
        $fa_email_subject->setEditable(false);
        $fa_email_content->setEditable(false);

        $chat_rules = new BElement('pre');
        $chat_rules->add('{
  "rules": {
    "users": {
      "$uid": {
        ".read": "auth.uid != null",
        ".write": "auth.uid === $uid"
      }
    },
    "rooms": {
      "$roomId": {
        ".write": "auth != null",
        ".read": "root.child(\'userRooms\').child(auth.uid).child($roomId).exists()",
        "participants": {
          "$participantId": {
            
          }
        },
        "messages": {
          ".indexOn": "timestamp",
          "$messageId": {
               ".validate": "newData.child(\'sender\').val() === auth.uid && root.child(\'rooms\').child($roomId).child(\'participants\').child(auth.uid).exists()"
          }
          
        },
        "lastMessage": {
          ".validate": "newData.hasChildren([\'text\', \'timestamp\', \'sender\']) &&
                        newData.child(\'text\').isString() &&
                        newData.child(\'timestamp\').isNumber() &&
                        newData.child(\'sender\').isString()"
        }
      }
    },
    "userRooms": {
      ".write": "auth != null",
      "$uid": {
        ".read": "auth.uid === $uid",
        ".write": "auth.uid === $uid",
        "$roomId": {

        }
      }
    },
    ".read": false,
    ".write": false
  }
}

');
        
        $this->form->appendPage(_t('E-mail settings'));

        $e2fa_by_email->setChangeAction(new TAction([$this, 'onChange2FAByEmail']));
        $google_recaptcha->setChangeAction(new TAction([$this, 'onChangeGoogleRecaptcha']));

        $row = $this->form->addFields( [new TLabel(_t('Mail from'), null, null, null, '100%'), $mail_from], [new TLabel(_t('SMTP Auth'), null, null, null, '100%'), $smtp_auth] );
        $row->layout = ['col-sm-6', 'col-sm-6'];

        $row = $this->form->addFields( [new TLabel(_t('SMTP Host'), null, null, null, '100%'), $smtp_host], [new TLabel(_t('SMTP Port'), null, null, null, '100%'), $smtp_port]  );
        $row->layout = ['col-sm-6', 'col-sm-6'];

        $row = $this->form->addFields( [new TLabel(_t('SMTP User'), null, null, null, '100%'), $smtp_user], [new TLabel(_t('SMTP Pass'), null, null, null, '100%'), $smtp_pass] );
        $row->layout = ['col-sm-6', 'col-sm-6'];

        $row = $this->form->addFields( [new TLabel(_t('Support mail'), null, null, null, '100%'), $mail_support] );
        $row->layout = ['col-sm-6'];

        $this->form->appendPage(_t('Navigation'));

        $row = $this->form->addFields( [$single_tab_mode] );
        $row->layout = ['col-sm-12'];
        
        $this->form->appendPage(_t('Security'));

        $row = $this->form->addFields( [$strong_passowrd]);
        $row->layout = ['col-sm-12'];
        
        $row = $this->form->addFields( [$google_recaptcha] );
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields( [new TLabel('Google reCAPTCHA site key', null, null, null, '100%'), $google_recaptcha_site_key], [new TLabel('Google reCAPTCHA secret key', null, null, null, '100%'), $google_recaptcha_secret_key] );
        $row->layout = ['col-sm-6', 'col-sm-6'];

        $row = $this->form->addFields( [ new TFormSeparator('')] );
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields( [$e2fa_by_email], [$e2fa_by_google_auth] );
        $row->layout = ['col-sm-6', 'col-sm-6'];

        $row = $this->form->addFields( [new TLabel(_t('Email subject'), null, null, null, '100%'), $fa_email_subject]); 
        $row->layout = ['col-sm-6'];
        
        $row = $this->form->addFields([new TLabel(_t('Email content')."<br> <small>"._t('Available variables:').'{$code}, {$name} </small>', null, null, null, '100%'), $fa_email_content] );
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields( [ new TFormSeparator('')] );
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields( [new TLabel(_t('Terms of use and privacy policy'), null, null, null, '100%'), $term_policy] );
        $row->layout = ['col-sm-12'];
        
        $this->form->appendPage(_t('Maintenance'));
        
        $row = $this->form->addFields( [$maintenance_enabled] );
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields( [new TLabel(_t('Users who can log into the system'), null, null, null, '100%'), $maintenance_users] );
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields( [new TLabel(_t('Message when attempting to log in'), null, null, null, '100%'), $maintenance_message] );
        $row->layout = ['col-sm-12'];

        $this->form->appendPage('Firebase');
        $row = $this->form->addFields( [new TLabel(_t('JSON configuration'), null, null, null, '100%'), $firebase_json] );
        $row->layout = ['col-sm-12'];
        $row = $this->form->addFields( [new TLabel('Firebase web config', null, null, null, '100%'), $firebase_config] );
        $row->layout = ['col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('Chat rules'), null, null, null, '100%'), $chat_rules] );
        $row->layout = ['col-sm-12'];
        
        $this->form->appendPage(_t('Internal chat'));
        $row = $this->form->addFields( [ $internal_chat ] );
        $row->layout = ['col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('User groups unauthorized for chat usage'), null, null, null, '100%'), $internal_chat_unauthorized_groups] );
        $row->layout = ['col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('Users unauthorized for chat usage'), null, null, null, '100%'), $internal_chat_unauthorized_users] );
        $row->layout = ['col-sm-12'];
        

        $firebase_json->setSize('100%', 250);
        $firebase_config->setSize('100%', 250);
        $mail_from->setSize('100%');
        $smtp_auth->setSize('100%');
        $smtp_host->setSize('100%');
        $smtp_port->setSize('100%');
        $smtp_user->setSize('100%');
        $smtp_pass->setSize('100%');
        $fa_email_subject->setSize('100%');
        $fa_email_content->setSize('100%', 250);
        $mail_support->setSize('100%');
        $term_policy->setSize('100%', 250);
        $maintenance_message->setSize('100%', 70);
        $maintenance_users->setSize('100%');
        $google_recaptcha_site_key->setSize('100%');
        $google_recaptcha_secret_key->setSize('100%');
        $internal_chat_unauthorized_users->setSize('100%', 70);
        $internal_chat_unauthorized_groups->setSize('100%', 70);
        
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave'], ['static'=>1]), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        
        $container = new TVBox;
        $container->{'style'} = 'width: 100%;';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($this->form);
        parent::add($container);
    }

    public static function onChange2FAByEmail($param)
    {
        if ( $param['key'] == 'T' )
        {
            TEntry::enableField('form_preferences', '2fa_email_subject');
            THtmlEditor::enableField('form_preferences', '2fa_email_content');
        }
        else
        {
            TEntry::disableField('form_preferences', '2fa_email_subject');
            THtmlEditor::disableField('form_preferences', '2fa_email_content');
        }
    }

    public static function onChangeGoogleRecaptcha($param)
    {
        if ( $param['key'] == 'T' )
        {
            TPassword::enableField('form_preferences', 'google_recaptcha_site_key');
            TPassword::enableField('form_preferences', 'google_recaptcha_secret_key');
        }
        else
        {
            TPassword::disableField('form_preferences', 'google_recaptcha_site_key');
            TPassword::disableField('form_preferences', 'google_recaptcha_secret_key');
        }
    }
    
    /**
     * Carrega o formulário de preferências
     */
    function onEdit($param)
    {
        try
        {
            // open a transaction with database
            TTransaction::open($this->database);
            
            $preferences = SystemPreference::getAllPreferences();
            unset($preferences['smtp_pass']);

            if ($preferences)
            {
                $this->form->setData((object) $preferences);

                if(!empty($preferences['google_recaptcha']) && $preferences['google_recaptcha'] == 'T')
                {
                    $this->form->getField('google_recaptcha_site_key')->setEditable(true);
                    $this->form->getField('google_recaptcha_secret_key')->setEditable(true);
                }

                if(!empty($preferences['2fa_by_email']) && $preferences['2fa_by_email'] == 'T')
                {
                    $this->form->getField('2fa_email_subject')->setEditable(true);
                    $this->form->getField('2fa_email_content')->setEditable(true);
                }
            } 
            
            // close the transaction
            TTransaction::close();
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }
    
    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    function onSave()
    {
        try
        {
            // open a transaction with database
            TTransaction::open($this->database);
            
            // get the form data
            $data = $this->form->getData();
            $data_array = (array) $data;
            
            $old_term_policy = SystemPreference::find('term_policy');
            
            if (is_null($old_term_policy) || $data_array['term_policy'] !== $old_term_policy->preference)
            {
                SystemUsers::where('accepted_term_policy', '=', 'Y')
                            ->set('accepted_term_policy', 'N')
                            ->set('accepted_term_policy_at', '')
                            ->update();
            }

            if (empty(trim($data_array['smtp_pass'])))
            {
                unset($data_array['smtp_pass']);
            }

            foreach ($data_array as $property => $preference)
            {
                if($preference)
                {
                    if(is_array($preference))
                    {
                        $preference = implode(',', $preference);
                    }

                    $object = new SystemPreference;
                    $object->{'id'}    = $property;
                    $object->{'preference'} = $preference;
                    $object->store();
                }
                else
                {
                    SystemPreference::where('id', '=', $property)->delete();
                }
            }
            
            // fill the form with the active record data
            $this->form->setData($data);
            
            // close the transaction
            TTransaction::close();
            
            // shows the success message
            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'));
            // reload the listing
        }
        catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData($this->activeRecord);
            
            // fill the form with the active record data
            $this->form->setData($object);
            
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            
            // undo all pending operations
            TTransaction::rollback();
        }
    }
}
