<?php
/**
 * SystemProfileForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemProfileForm extends TPage
{
    private $form;
    
    public function __construct()
    {
        parent::__construct();
        
        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }
        
        $this->form = new BootstrapFormBuilder;
        $this->form->setFormTitle(_t('Profile'));
        $this->form->setClientValidation(true);
        $this->form->enableCSRFProtection();
        
        $name  = new TEntry('name');
        $login = new TEntry('login');
        $email = new TEntry('email');
        $photo = new TFile('photo');
        $photo->enablePHPFileUploadLimit();
        $password1 = new TPassword('password1');
        $password2 = new TPassword('password2');
        $login->setEditable(FALSE);
        $photo->setAllowedExtensions( ['jpg'] );
        $photo->setLimitUploadSize(2);
        
        $name->setSize('100%');
        $login->setSize('100%');
        $email->setSize('100%');
        $photo->setSize('100%');
        $password1->setSize('100%');
        $password2->setSize('100%');
        
        $name->addValidation(_t('Name'), new TRequiredValidator);
        $login->addValidation(_t('Name'), new TRequiredValidator);
        $email->addValidation(_t('Name'), new TRequiredValidator);
        $email->addValidation( _t('Email'), new TEmailValidator);

        if(SystemPreferenceService::isStrongPasswordEnabled())
        {
            $password1->enableStrongPasswordValidation(_t('Password'));
            $password2->enableStrongPasswordValidation(_t('Password confirmation'));
        }
        
        $row = $this->form->addFields( [new TLabel(_t('Name'), '#ff0000', '14px', null, '100%'),$name]);
        $row->layout = [' col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('Login'), '#ff0000', '14px', null, '100%'),$login]);
        $row->layout = [' col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('Email'), '#ff0000', '14px', null, '100%'),$email]);
        $row->layout = [' col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('Photo').'<small>(.jpg) </small> ', '#ff0000', '14px', null, '100%'),$photo]);
        $row->layout = [' col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('Password'), '#ff0000', '14px', null, '100%'),$password1]);
        $row->layout = [' col-sm-12'];
        $row = $this->form->addFields( [new TLabel(_t('Password confirmation'), '#ff0000', '14px', null, '100%'),$password2]);
        $row->layout = [' col-sm-12'];
        
        $btn = $this->form->addAction(_t('Save'), new TAction([$this, 'onSave']), 'fa:save');
        $btn->class = 'btn btn-sm btn-primary';
        
        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel(_t("Close"));
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);
        
        parent::add($this->form);
    }
    
    public function onEdit($param)
    {
        try
        {
            TTransaction::open('permission');
            $login = SystemUsers::newFromLogin( TSession::getValue('login') );
            $this->form->setData($login);
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    public function onSave($param)
    {
        try
        {
            $this->form->validate();
            
            $object = $this->form->getData();
            
            TTransaction::open('permission');
            $user = SystemUsers::newFromLogin( TSession::getValue('login') );
            $user->name = $object->name;
            $user->email = $object->email;
            
            TSession::setValue('username', $user->name);
            TSession::setValue('usermail', $user->email);
            
            if( $object->password1 )
            {
                if( $object->password1 != $object->password2 )
                {
                    throw new Exception(_t('The passwords do not match'));
                }
                
                $user->password = password_hash($object->password1, PASSWORD_BCRYPT);
            }
            else
            {
                unset($user->password);
            }
            
            $user->store();
            
            if ($object->photo)
            {
                $source_file   = 'tmp/'.$object->photo;
                $target_file   = 'app/images/photos/' . TSession::getValue('login') . '.jpg';
                $finfo         = new finfo(FILEINFO_MIME_TYPE);
                
                if (file_exists($source_file))
                {
                    // move to the target directory
                    rename($source_file, $target_file);
                }
            }
            
            $this->form->setData($object);
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'));
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}
