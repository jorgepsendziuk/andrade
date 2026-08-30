<?php
use Adianti\Core\AdiantiCoreTranslator;

/**
 * ApplicationTranslator
 *
 * @version    7.0
 * @package    util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class ApplicationTranslator
{
    private static $instance; // singleton instance
    private $lang;            // target language
    private $messages;
    private $sourceMessages;
    
    /**
     * Class Constructor
     */
    private function __construct()
    {
        $this->messages = [];
        $this->messages['en'] = [];
        $this->messages['en'][] = 'File not found';
        $this->messages['en'][] = 'Search';
        $this->messages['en'][] = 'Register';
        $this->messages['en'][] = 'Record saved';
        $this->messages['en'][] = 'Do you really want to delete ?';
        $this->messages['en'][] = 'Record deleted';
        $this->messages['en'][] = 'Function';
        $this->messages['en'][] = 'Table';
        $this->messages['en'][] = 'Tool';
        $this->messages['en'][] = 'Data';
        $this->messages['en'][] = 'Open';
        $this->messages['en'][] = 'New';
        $this->messages['en'][] = 'Save';
        $this->messages['en'][] = 'Find';
        $this->messages['en'][] = 'Edit';
        $this->messages['en'][] = 'Delete';
        $this->messages['en'][] = 'Cancel';
        $this->messages['en'][] = 'Yes';
        $this->messages['en'][] = 'No';
        $this->messages['en'][] = 'January';
        $this->messages['en'][] = 'February';
        $this->messages['en'][] = 'March';
        $this->messages['en'][] = 'April';
        $this->messages['en'][] = 'May';
        $this->messages['en'][] = 'June';
        $this->messages['en'][] = 'July';
        $this->messages['en'][] = 'August';
        $this->messages['en'][] = 'September';
        $this->messages['en'][] = 'October';
        $this->messages['en'][] = 'November';
        $this->messages['en'][] = 'December';
        $this->messages['en'][] = 'Today';
        $this->messages['en'][] = 'Close';
        $this->messages['en'][] = 'The field ^1 can not be less than ^2 characters';
        $this->messages['en'][] = 'The field ^1 can not be greater than ^2 characters';
        $this->messages['en'][] = 'The field ^1 can not be less than ^2';
        $this->messages['en'][] = 'The field ^1 can not be greater than ^2';
        $this->messages['en'][] = 'The field ^1 is required';
        $this->messages['en'][] = 'The field ^1 has not a valid CNPJ';
        $this->messages['en'][] = 'The field ^1 has not a valid CPF';
        $this->messages['en'][] = 'The field ^1 contains an invalid e-mail';
        $this->messages['en'][] = 'Permission denied';
        $this->messages['en'][] = 'Generate';
        $this->messages['en'][] = 'List';
        $this->messages['en'][] = 'Wrong password';
        $this->messages['en'][] = 'User not found';
        $this->messages['en'][] = 'User';
        $this->messages['en'][] = 'Users';
        $this->messages['en'][] = 'Password';
        $this->messages['en'][] = 'Login';
        $this->messages['en'][] = 'Name';
        $this->messages['en'][] = 'Group';
        $this->messages['en'][] = 'Groups';
        $this->messages['en'][] = 'Program';
        $this->messages['en'][] = 'Programs';
        $this->messages['en'][] = 'Back to the listing';
        $this->messages['en'][] = 'Controller';
        $this->messages['en'][] = 'Email';
        $this->messages['en'][] = 'Record Updated';
        $this->messages['en'][] = 'Password confirmation';
        $this->messages['en'][] = 'Front page';
        $this->messages['en'][] = 'Page name';
        $this->messages['en'][] = 'The passwords do not match';
        $this->messages['en'][] = 'Log in';
        $this->messages['en'][] = 'Date';
        $this->messages['en'][] = 'Column';
        $this->messages['en'][] = 'Operation';
        $this->messages['en'][] = 'Old value';
        $this->messages['en'][] = 'New value';
        $this->messages['en'][] = 'Database';
        $this->messages['en'][] = 'Profile';
        $this->messages['en'][] = 'Change password';
        $this->messages['en'][] = 'Leave empty to keep old password';
        $this->messages['en'][] = 'Results';
        $this->messages['en'][] = 'Invalid command';
        $this->messages['en'][] = '^1 records shown';
        $this->messages['en'][] = 'Administration';
        $this->messages['en'][] = 'SQL Panel';
        $this->messages['en'][] = 'Access Log';
        $this->messages['en'][] = 'Change Log';
        $this->messages['en'][] = 'SQL Log';
        $this->messages['en'][] = 'Clear form';
        $this->messages['en'][] = 'Send';
        $this->messages['en'][] = 'Message';
        $this->messages['en'][] = 'Messages';
        $this->messages['en'][] = 'Subject';
        $this->messages['en'][] = 'Message sent successfully';
        $this->messages['en'][] = 'Check as read';
        $this->messages['en'][] = 'Check as unread';
        $this->messages['en'][] = 'Action';
        $this->messages['en'][] = 'Read';
        $this->messages['en'][] = 'From';
        $this->messages['en'][] = 'Checked';
        $this->messages['en'][] = 'Object ^1 not found in ^2';
        $this->messages['en'][] = 'Notification';
        $this->messages['en'][] = 'Notifications';
        $this->messages['en'][] = 'Categories';
        $this->messages['en'][] = 'Send document';
        $this->messages['en'][] = 'My documents';
        $this->messages['en'][] = 'Shared with me';
        $this->messages['en'][] = 'Document';
        $this->messages['en'][] = 'File';
        $this->messages['en'][] = 'Title';
        $this->messages['en'][] = 'Description';
        $this->messages['en'][] = 'Category';
        $this->messages['en'][] = 'Submission date';
        $this->messages['en'][] = 'Archive date';
        $this->messages['en'][] = 'Upload';
        $this->messages['en'][] = 'Download';
        $this->messages['en'][] = 'Next';
        $this->messages['en'][] = 'Documents';
        $this->messages['en'][] = 'Permission';
        $this->messages['en'][] = 'Unit';
        $this->messages['en'][] = 'Units';
        $this->messages['en'][] = 'Add';
        $this->messages['en'][] = 'Active';
        $this->messages['en'][] = 'Activate/Deactivate';
        $this->messages['en'][] = 'Inactive user';
        $this->messages['en'][] = 'Send message';
        $this->messages['en'][] = 'Read messages';
        $this->messages['en'][] = 'An user with this login is already registered';
        $this->messages['en'][] = 'Access Stats';
        $this->messages['en'][] = 'Accesses';
        $this->messages['en'][] = 'Preferences';
        $this->messages['en'][] = 'Mail from';
        $this->messages['en'][] = 'SMTP Auth';
        $this->messages['en'][] = 'SMTP Host';
        $this->messages['en'][] = 'SMTP Port';
        $this->messages['en'][] = 'SMTP User';
        $this->messages['en'][] = 'SMTP Pass';
        $this->messages['en'][] = 'Ticket';
        $this->messages['en'][] = 'Open ticket';
        $this->messages['en'][] = 'Support mail';
        $this->messages['en'][] = 'Day';
        $this->messages['en'][] = 'Folders';
        $this->messages['en'][] = 'Compose';
        $this->messages['en'][] = 'Inbox';
        $this->messages['en'][] = 'Sent';
        $this->messages['en'][] = 'Archived';
        $this->messages['en'][] = 'Archive';
        $this->messages['en'][] = 'Recover';
        $this->messages['en'][] = 'Value';
        $this->messages['en'][] = 'View all';
        $this->messages['en'][] = 'Reload';
        $this->messages['en'][] = 'Back';
        $this->messages['en'][] = 'Clear';
        $this->messages['en'][] = 'View';
        $this->messages['en'][] = 'No records found';
        $this->messages['en'][] = 'Drawing successfully generated';
        $this->messages['en'][] = 'QR Codes successfully generated';
        $this->messages['en'][] = 'Barcodes successfully generated';
        $this->messages['en'][] = 'Document successfully generated';
        $this->messages['en'][] = 'Value';
        $this->messages['en'][] = 'User';
        $this->messages['en'][] = 'Password';
        $this->messages['en'][] = 'Port';
        $this->messages['en'][] = 'Database type';
        $this->messages['en'][] = 'Root user';
        $this->messages['en'][] = 'Root password';
        $this->messages['en'][] = 'Create database/user';
        $this->messages['en'][] = 'Test connection';
        $this->messages['en'][] = 'Database name';
        $this->messages['en'][] = 'Insert permissions/programs';
        $this->messages['en'][] = 'Database and user created successfully';
        $this->messages['en'][] = 'Permissions and programs successfully inserted';
        $this->messages['en'][] = 'Update config';
        $this->messages['en'][] = 'Configuration file: ^1 updated successfully';
        $this->messages['en'][] = 'Connection successfully completed';
        $this->messages['en'][] = "The database ^1 doesn't exists";
        $this->messages['en'][] = 'Permissions/programs successfully inserted';
        $this->messages['en'][] = 'Programs/permissions have already been inserted';
        $this->messages['en'][] = 'Installing your application';
        $this->messages['en'][] = 'PHP version verification and installed extensions';
        $this->messages['en'][] = 'PHP verification';
        $this->messages['en'][] = 'Directory and files verification';
        $this->messages['en'][] = 'Database configuration/creation';
        $this->messages['en'][] = 'Admin user';
        $this->messages['en'][] = 'Admin password';
        $this->messages['en'][] = 'Insert data';
        $this->messages['en'][] = 'Of database:';
        $this->messages['en'][] = 'Connecton to database ^1 failed';
        $this->messages['en'][] = 'Install';
        $this->messages['en'][] = 'Databases successfully installed';
        $this->messages['en'][] = 'Databases have already been installed';
        $this->messages['en'][] = 'Main unit';
        $this->messages['en'][] = 'Time';
        $this->messages['en'][] = 'Type';
        $this->messages['en'][] = 'Failed to read error log (^1)';
        $this->messages['en'][] = 'Error log (^1) is not writable by web server user, so the messages may be incomplete';
        $this->messages['en'][] = 'Check the owner of the log file. He must be the same as the web user (usually www-data, www, etc)';
        $this->messages['en'][] = 'Error log is empty or has not been configured correctly. Define the error log file, setting <b>error_log</b> at php.ini';
        $this->messages['en'][] = 'Errors are not being logged. Please turn <b>log_errors = On</b> at php.ini';
        $this->messages['en'][] = 'Errors are not currently being displayd because the <b>display_errors</b> is set to Off in php.ini';
        $this->messages['en'][] = 'This configuration is usually recommended for production, not development purposes';
        $this->messages['en'][] = 'The php.ini current location is <b>^1</b>';
        $this->messages['en'][] = 'The error log current location is <b>^1</b>';
        $this->messages['en'][] = 'PHP Log';
        $this->messages['en'][] = 'Database explorer';
        $this->messages['en'][] = 'Tables';
        $this->messages['en'][] = 'Report generated. Please, enable popups';
        $this->messages['en'][] = 'File saved';
        $this->messages['en'][] = 'Edit page';
        $this->messages['en'][] = 'Update page';
        $this->messages['en'][] = 'Module';
        $this->messages['en'][] = 'Directory';
        $this->messages['en'][] = 'Source code';
        $this->messages['en'][] = 'Invalid return';
        $this->messages['en'][] = 'Page';
        $this->messages['en'][] = 'Connection failed';
        $this->messages['en'][] = 'Summary database install';
        $this->messages['en'][] = 'No write permission on file';
        $this->messages['en'][] = 'In order to continue with the installation you must grant read permission to the directory';
        $this->messages['en'][] = 'In order to continue with the installation you must grant write permission to the directory';
        $this->messages['en'][] = 'Installed';
        $this->messages['en'][] = 'Path';
        $this->messages['en'][] = 'File';
        $this->messages['en'][] = 'Write';
        $this->messages['en'][] = 'Read';
        $this->messages['en'][] = 'In order to continue with the installation you must grant read permission to the file';
        $this->messages['en'][] = 'In order to continue with the installation you must grant write permission to the file';
        $this->messages['en'][] = 'Photo';
        $this->messages['en'][] = 'Reset password';
        $this->messages['en'][] = 'A new seed is required in the application.ini for security reasons';
        $this->messages['en'][] = 'Password reset';
        $this->messages['en'][] = 'Token expired. This operation is not allowed';
        $this->messages['en'][] = 'The password has been changed';
        $this->messages['en'][] = 'An user with this e-mail is already registered';
        $this->messages['en'][] = 'Invalid LDAP credentials';
        $this->messages['en'][] = 'Update menu overwriting existing file?';
        $this->messages['en'][] = 'Menu updated successfully';
        $this->messages['en'][] = 'Menu path';
        $this->messages['en'][] = 'Add to menu';
        $this->messages['en'][] = 'Remove from menu';
        $this->messages['en'][] = 'Item removed from menu';
        $this->messages['en'][] = 'Item added to menu';
        $this->messages['en'][] = 'Icon';
        $this->messages['en'][] = 'User registration';
        $this->messages['en'][] = 'The user registration is disabled';
        $this->messages['en'][] = 'The password reset is disabled';
        $this->messages['en'][] = 'Account created';
        $this->messages['en'][] = 'Create account';
        $this->messages['en'][] = 'If you want to reinstall edit the file app/config/install.ini and change installed = 1 to installed = 0. Erase the content in app/config/installed.ini too';
        $this->messages['en'][] = 'Authorization error';
        $this->messages['en'][] = 'Exit';
        $this->messages['en'][] = 'REST key not defined';
        $this->messages['en'][] = 'Local';
        $this->messages['en'][] = 'Remote';
        $this->messages['en'][] = 'Success';
        $this->messages['en'][] = 'Error';
        $this->messages['en'][] = 'Status';
        $this->messages['en'][] = 'Update permissions?';
        $this->messages['en'][] = 'Changed';
        $this->messages['en'][] = 'Add item above';
        $this->messages['en'][] = 'Add item below';
        $this->messages['en'][] = 'Add child item';
        $this->messages['en'][] = 'Remove item';
        $this->messages['en'][] = 'Move item';
        $this->messages['en'][] = 'Menu editor';
        $this->messages['en'][] = 'Order';
        $this->messages['en'][] = 'Label';
        $this->messages['en'][] = 'Color';
        $this->messages['en'][] = 'Menu saved';
        $this->messages['en'][] = 'Clone';
        $this->messages['en'][] = 'Impersonation';
        $this->messages['en'][] = 'Impersonated';
        $this->messages['en'][] = 'Execution trace';
        $this->messages['en'][] = 'Session';
        $this->messages['en'][] = 'Request Log';
        $this->messages['en'][] = 'Method';
        $this->messages['en'][] = 'Request';
        $this->messages['en'][] = 'Users by group';
        $this->messages['en'][] = 'Count';
        $this->messages['en'][] = 'Users by unit';
        $this->messages['en'][] = 'Save as PDF';
        $this->messages['en'][] = 'Save as CSV';
        $this->messages['en'][] = 'Save as XML';
        $this->messages['en'][] = 'Export';
        $this->messages['en'][] = 'System information';
        $this->messages['en'][] = 'RAM Memory';
        $this->messages['en'][] = 'Using/Total';
        $this->messages['en'][] = 'Free';
        $this->messages['en'][] = 'Percentage used';
        $this->messages['en'][] = 'CPU usage';
        $this->messages['en'][] = 'Vendor';
        $this->messages['en'][] = 'Model';
        $this->messages['en'][] = 'Current Frequency';
        $this->messages['en'][] = 'Webserver and Process';
        $this->messages['en'][] = 'Disk devices';
        $this->messages['en'][] = 'Device';
        $this->messages['en'][] = 'Mount point';
        $this->messages['en'][] = 'Filesystem';
        $this->messages['en'][] = 'Network devices';
        $this->messages['en'][] = 'Device name';
        $this->messages['en'][] = 'Port speed';
        $this->messages['en'][] = 'Sent';
        $this->messages['en'][] = 'Recieved';
        $this->messages['en'][] = 'Print';
        $this->messages['en'][] = 'Delete session var';
        $this->messages['en'][] = 'Impersonated by';
        $this->messages['en'][] = 'Unauthorized access to that unit';
        $this->messages['en'][] = 'Files diff';
        $this->messages['en'][] = 'Removed';
        $this->messages['en'][] = 'Equal';
        $this->messages['en'][] = 'Modified';
        $this->messages['en'][] = 'Terms of use and privacy policy';
        $this->messages['en'][] = 'Accept';
        $this->messages['en'][] = 'I have read and agree to the terms of use and privacy policy';
        $this->messages['en'][] = 'You need read and agree to the terms of use and privacy policy';
        $this->messages['en'][] = 'Login to your account';
        $this->messages['en'][] = 'You have just successfully logged in to ^1. If you do not recognize this login, contact technical support';
        $this->messages['en'][] = 'Current unit';
        $this->messages['en'][] = 'Choose the unit';
        $this->messages['en'][] = 'Change Unit';
        $this->messages['en'][] = 'Unit is required';
        $this->messages['en'][] = 'Unit changed!';
        $this->messages['en'][] = 'No permission to manage this record!';
        $this->messages['en'][] = 'Actions';
        $this->messages['en'][] = 'Refresh';
        $this->messages['en'][] = 'Clear filters';
        $this->messages['en'][] = 'Invalid username or password';
        $this->messages['en'][] = 'E-mail settings';
        $this->messages['en'][] = 'Security settings';
        $this->messages['en'][] = 'Enable strong password';
        $this->messages['en'][] = 'Maintenance';
        $this->messages['en'][] = 'Maintenance mode';
        $this->messages['en'][] = 'Message when attempting to log in';
        $this->messages['en'][] = 'Users who can log into the system';
        $this->messages['en'][] = 'When defining actions, they become mandatory, meaning the user will only be able to execute them if they are associated with a group';
        $this->messages['en'][] = 'Setup 2FA';
        $this->messages['en'][] = 'Continue';
        $this->messages['en'][] = 'You need to select a 2FA method';
        $this->messages['en'][] = 'Code sent';
        $this->messages['en'][] = 'Verify and activate';
        $this->messages['en'][] = 'Invalid verification code. Request a new code.';
        $this->messages['en'][] = 'Code expired. Request a new code.';
        $this->messages['en'][] = 'Error sending email:';
        $this->messages['en'][] = 'Verification code';
        $this->messages['en'][] = 'Your verification code is: ^1';
        $this->messages['en'][] = '2FA by email successfully activated!';
        $this->messages['en'][] = 'Enter the 6-digit code sent to your email';
        $this->messages['en'][] = 'VERIFY';
        $this->messages['en'][] = 'Two-step verification';
        $this->messages['en'][] = 'Enter the 6-digit Google Authenticator code';
        $this->messages['en'][] = "Haven't received the code?";
        $this->messages['en'][] = "Disable";
        $this->messages['en'][] = "Do you really want to disable 2FA ?";
        $this->messages['en'][] = "2FA successfully disabled!";
        $this->messages['en'][] = "Configure Email Authentication";
        $this->messages['en'][] = "Confirm your email";
        $this->messages['en'][] = "You will receive verification codes at this email:";
        $this->messages['en'][] = "Enter the code";
        $this->messages['en'][] = "Resend code";
        $this->messages['en'][] = "2FA by Google Authenticator successfully activated!";
        $this->messages['en'][] = "Install the app";
        $this->messages['en'][] = 'Scan the QR Code';
        $this->messages['en'][] = 'Or enter the code manually:';
        $this->messages['en'][] = 'Check the configuration';
        $this->messages['en'][] = 'Enter the code shown in Google Authenticator';
        $this->messages['en'][] = 'Enable Google reCAPTCHA';
        $this->messages['en'][] = 'Enable 2FA by Google Authenticator';
        $this->messages['en'][] = 'Enable 2FA by email';
        $this->messages['en'][] = 'Email subject';
        $this->messages['en'][] = 'Email content';
        $this->messages['en'][] = 'Available variables:';
        $this->messages['en'][] = 'reCAPTCHA verification failed. Please try again.';
        $this->messages['en'][] = '2FA by email is not enabled in the system';
        $this->messages['en'][] = '2FA by Google Aythenticator is not enabled in the system';
        $this->messages['en'][] = 'Destination table';
        $this->messages['en'][] = 'Destination field';
        $this->messages['en'][] = 'Origin field (CSV)';
        $this->messages['en'][] = 'Mapping of columns';
        $this->messages['en'][] = 'Import';
        $this->messages['en'][] = 'Format not supported. Please upload a CSV file.';
        $this->messages['en'][] = 'Error creating a copy of the file in the tmp folder. Please check the permissions of the tmp folder of your project.';
        $this->messages['en'][] = 'No file was uploaded.';
        $this->messages['en'][] = 'No valid mapping defined.';
        $this->messages['en'][] = 'Unsupported driver:';
        $this->messages['en'][] = 'CSV file could not be opened.';
        $this->messages['en'][] = 'Import completed successfully!<br> Processed records: ^1';
        $this->messages['en'][] = 'JSON configuration';
        $this->messages['en'][] = 'Internal chat';
        $this->messages['en'][] = 'To use the internal chat, it is necessary to have provided the Firebase JSON configuration.';
        $this->messages['en'][] = 'Enable internal chat';
        $this->messages['en'][] = 'User groups unauthorized for chat usage';
        $this->messages['en'][] = 'Users unauthorized for chat usage';
        $this->messages['en'][] = 'Disconnect user?';
        $this->messages['en'][] = 'Last action executed';
        $this->messages['en'][] = 'Disconnect user';
        $this->messages['en'][] = 'New conversation';
        $this->messages['en'][] = 'Start conversation';
        $this->messages['en'][] = 'New group conversation';
        $this->messages['en'][] = 'Group name';
        $this->messages['en'][] = 'Participants';
        $this->messages['en'][] = 'Add group participants';
        $this->messages['en'][] = 'Data importing';
        $this->messages['en'][] = 'User monitoring';
        $this->messages['en'][] = 'Navigation';
        $this->messages['en'][] = 'Restrict system access to a single browser tab';
        $this->messages['en'][] = 'Security';
        $this->messages['en'][] = 'Allows system users to chat with each other or in groups';
        $this->messages['en'][] = 'When enabled, only authorized users will be able to access the system';
        $this->messages['en'][] = 'Strong password';
        $this->messages['en'][] = 'Requires that the user password contains 8 characters, including numbers, uppercase letters, lowercase letters, and special characters';
        $this->messages['en'][] = 'When enabled, adds reCAPTCHA verification to the login screen for protection against bots';
        $this->messages['en'][] = 'Enable 2FA by email';
        $this->messages['en'][] = 'When enabled, users will be able to use 2FA by email';
        $this->messages['en'][] = 'Enable 2FA by Google Authenticator';
        $this->messages['en'][] = 'When enabled, users will be able to use 2FA by Google Authenticator';
        $this->messages['en'][] = 'Chat rules';
        $this->messages['en'][] = 'Import data';

        //fim
        
        $this->messages['pt'] = [];
        $this->messages['pt'][] = 'Arquivo não encontrado';
        $this->messages['pt'][] = 'Buscar';
        $this->messages['pt'][] = 'Cadastrar';
        $this->messages['pt'][] = 'Registro salvo';
        $this->messages['pt'][] = 'Deseja realmente excluir ?';
        $this->messages['pt'][] = 'Registro excluído';
        $this->messages['pt'][] = 'Função';
        $this->messages['pt'][] = 'Tabela';
        $this->messages['pt'][] = 'Ferramenta';
        $this->messages['pt'][] = 'Dados';
        $this->messages['pt'][] = 'Abrir';
        $this->messages['pt'][] = 'Novo';
        $this->messages['pt'][] = 'Salvar';
        $this->messages['pt'][] = 'Buscar';
        $this->messages['pt'][] = 'Editar';
        $this->messages['pt'][] = 'Excluir';
        $this->messages['pt'][] = 'Cancelar';
        $this->messages['pt'][] = 'Sim';
        $this->messages['pt'][] = 'Não';
        $this->messages['pt'][] = 'Janeiro';
        $this->messages['pt'][] = 'Fevereiro';
        $this->messages['pt'][] = 'Março';
        $this->messages['pt'][] = 'Abril';
        $this->messages['pt'][] = 'Maio';
        $this->messages['pt'][] = 'Junho';
        $this->messages['pt'][] = 'Julho';
        $this->messages['pt'][] = 'Agosto';
        $this->messages['pt'][] = 'Setembro';
        $this->messages['pt'][] = 'Outubro';
        $this->messages['pt'][] = 'Novembro';
        $this->messages['pt'][] = 'Dezembro';
        $this->messages['pt'][] = 'Hoje';
        $this->messages['pt'][] = 'Fechar';
        $this->messages['pt'][] = 'O campo ^1 não pode ter menos de ^2 caracteres';
        $this->messages['pt'][] = 'O campo ^1 não pode ter mais de ^2 caracteres';
        $this->messages['pt'][] = 'O campo ^1 não pode ser menor que ^2';
        $this->messages['pt'][] = 'O campo ^1 não pode ser maior que ^2';
        $this->messages['pt'][] = 'O campo ^1 é obrigatório';
        $this->messages['pt'][] = 'O campo ^1 não contém um CNPJ válido';
        $this->messages['pt'][] = 'O campo ^1 não contém um CPF válido';
        $this->messages['pt'][] = 'O campo ^1 contém um e-mail inválido';
        $this->messages['pt'][] = 'Permissão negada';
        $this->messages['pt'][] = 'Gerar';
        $this->messages['pt'][] = 'Listar';
        $this->messages['pt'][] = 'Senha errada';
        $this->messages['pt'][] = 'Usuário não encontrado';
        $this->messages['pt'][] = 'Usuário';
        $this->messages['pt'][] = 'Usuários';
        $this->messages['pt'][] = 'Senha';
        $this->messages['pt'][] = 'Login';
        $this->messages['pt'][] = 'Nome';
        $this->messages['pt'][] = 'Grupo';
        $this->messages['pt'][] = 'Grupos';
        $this->messages['pt'][] = 'Programa';
        $this->messages['pt'][] = 'Programas';
        $this->messages['pt'][] = 'Voltar para a listagem';
        $this->messages['pt'][] = 'Classe de controle';
        $this->messages['pt'][] = 'Email';
        $this->messages['pt'][] = 'Registro atualizado';
        $this->messages['pt'][] = 'Confirma senha';
        $this->messages['pt'][] = 'Tela inicial';
        $this->messages['pt'][] = 'Nome da Tela';
        $this->messages['pt'][] = 'As senhas não conferem';
        $this->messages['pt'][] = 'Entrar';
        $this->messages['pt'][] = 'Data';
        $this->messages['pt'][] = 'Coluna';
        $this->messages['pt'][] = 'Operação';
        $this->messages['pt'][] = 'Valor antigo';
        $this->messages['pt'][] = 'Valor novo';
        $this->messages['pt'][] = 'Banco de dados';
        $this->messages['pt'][] = 'Perfil';
        $this->messages['pt'][] = 'Mudar senha';
        $this->messages['pt'][] = 'Deixe vazio para manter a senha anterior';
        $this->messages['pt'][] = 'Resultados';
        $this->messages['pt'][] = 'Comando inválido';
        $this->messages['pt'][] = '^1 registros exibidos';
        $this->messages['pt'][] = 'Administração';
        $this->messages['pt'][] = 'Painel SQL';
        $this->messages['pt'][] = 'Log de acesso';
        $this->messages['pt'][] = 'Log de alterações';
        $this->messages['pt'][] = 'Log de SQL';
        $this->messages['pt'][] = 'Limpar formulário';
        $this->messages['pt'][] = 'Enviar';
        $this->messages['pt'][] = 'Mensagem';
        $this->messages['pt'][] = 'Mensagens';
        $this->messages['pt'][] = 'Assunto';
        $this->messages['pt'][] = 'Mensagem enviada com sucesso';
        $this->messages['pt'][] = 'Marcar como lida';
        $this->messages['pt'][] = 'Marcar como não lida';
        $this->messages['pt'][] = 'Ação';
        $this->messages['pt'][] = 'Ler';
        $this->messages['pt'][] = 'Origem';
        $this->messages['pt'][] = 'Verificado';
        $this->messages['pt'][] = 'Objeto ^1 não encontrado em ^2';
        $this->messages['pt'][] = 'Notificação';
        $this->messages['pt'][] = 'Notificações';
        $this->messages['pt'][] = 'Categorias';
        $this->messages['pt'][] = 'Enviar documentos';
        $this->messages['pt'][] = 'Meus documentos';
        $this->messages['pt'][] = 'Compartilhados comigo';
        $this->messages['pt'][] = 'Documento';
        $this->messages['pt'][] = 'Arquivo';
        $this->messages['pt'][] = 'Título';
        $this->messages['pt'][] = 'Descrição';
        $this->messages['pt'][] = 'Categoria';
        $this->messages['pt'][] = 'Data de submissão';
        $this->messages['pt'][] = 'Data de arquivamento';
        $this->messages['pt'][] = 'Upload';
        $this->messages['pt'][] = 'Download';
        $this->messages['pt'][] = 'Próximo';
        $this->messages['pt'][] = 'Documentos';
        $this->messages['pt'][] = 'Permissão';
        $this->messages['pt'][] = 'Unidade';
        $this->messages['pt'][] = 'Unidades';
        $this->messages['pt'][] = 'Adicionar';
        $this->messages['pt'][] = 'Ativo';
        $this->messages['pt'][] = 'Ativar/desativar';
        $this->messages['pt'][] = 'Usuário inativo';
        $this->messages['pt'][] = 'Envia mensagem';
        $this->messages['pt'][] = 'Ler mensagens';
        $this->messages['pt'][] = 'Um usuário já está cadastrado com este login';
        $this->messages['pt'][] = 'Estatísticas de acesso';
        $this->messages['pt'][] = 'Acessos';
        $this->messages['pt'][] = 'Preferências';
        $this->messages['pt'][] = 'E-mail de origem';
        $this->messages['pt'][] = 'Autentica SMTP';
        $this->messages['pt'][] = 'Host SMTP';
        $this->messages['pt'][] = 'Porta SMTP';
        $this->messages['pt'][] = 'Usuário SMTP';
        $this->messages['pt'][] = 'Senha SMTP';
        $this->messages['pt'][] = 'Ticket';
        $this->messages['pt'][] = 'Abrir ticket';
        $this->messages['pt'][] = 'Email de suporte';
        $this->messages['pt'][] = 'Dia';
        $this->messages['pt'][] = 'Pastas';
        $this->messages['pt'][] = 'Escrever';
        $this->messages['pt'][] = 'Entrada';
        $this->messages['pt'][] = 'Enviados';
        $this->messages['pt'][] = 'Arquivados';
        $this->messages['pt'][] = 'Arquivar';
        $this->messages['pt'][] = 'Recuperar';
        $this->messages['pt'][] = 'Valor';
        $this->messages['pt'][] = 'Ver todos';
        $this->messages['pt'][] = 'Recarregar';
        $this->messages['pt'][] = 'Voltar';
        $this->messages['pt'][] = 'Limpar';
        $this->messages['pt'][] = 'Visualizar';
        $this->messages['pt'][] = 'Nenhum registro foi encontrado';
        $this->messages['pt'][] = 'Desenho gerado com sucesso';
        $this->messages['pt'][] = 'QR codes gerados com sucesso';
        $this->messages['pt'][] = 'Códigos de barra gerados com sucesso';
        $this->messages['pt'][] = 'Documento gerado com sucesso';
        $this->messages['pt'][] = 'Valor';
        $this->messages['pt'][] = 'Usuário';
        $this->messages['pt'][] = 'Senha';
        $this->messages['pt'][] = 'Porta';
        $this->messages['pt'][] = 'Tipo da base de dados';
        $this->messages['pt'][] = 'Usuário admin';
        $this->messages['pt'][] = 'Senha do usuário admin';
        $this->messages['pt'][] = 'Criar base de dados/usuário';
        $this->messages['pt'][] = 'Testar conexão';
        $this->messages['pt'][] = 'Nome da base de dados';
        $this->messages['pt'][] = 'Inserir permissões/programas';
        $this->messages['pt'][] = 'Base de dados e usuário criado com sucesso';
        $this->messages['pt'][] = 'Permissões e programas inseridos com sucesso';
        $this->messages['pt'][] = 'Atualizar config';
        $this->messages['pt'][] = 'Arquivo de configuração: ^1 atualizado com sucesso';
        $this->messages['pt'][] = 'Conexão realizada com sucesso';
        $this->messages['pt'][] = 'A base de dados ^1 não existe';
        $this->messages['pt'][] = 'Permissões/programas inseridos com sucesso';
        $this->messages['pt'][] = 'Os programas/permissões já foram inseridos';
        $this->messages['pt'][] = 'Instalando a sua aplicação';
        $this->messages['pt'][] = 'Verificação da versão do PHP e extensões instaladas';
        $this->messages['pt'][] = 'Verificação do PHP';
        $this->messages['pt'][] = 'Verificação de diretórios e arquivos';
        $this->messages['pt'][] = 'Configuração/criação de base de dados';
        $this->messages['pt'][] = 'Usuário admin';
        $this->messages['pt'][] = 'Senha do usuário admin';
        $this->messages['pt'][] = 'Inserir dados';
        $this->messages['pt'][] = 'Da base de dados:';
        $this->messages['pt'][] = 'A conexão com a base de dados ^1 falhou';
        $this->messages['pt'][] = 'Instalar';
        $this->messages['pt'][] = 'Bases de dados instaladas com sucesso';
        $this->messages['pt'][] = 'As bases de dados já foram instaladas';
        $this->messages['pt'][] = 'Unidade principal';
        $this->messages['pt'][] = 'Hora';
        $this->messages['pt'][] = 'Tipo';
        $this->messages['pt'][] = 'Falha ao ler o log de erros (^1)';
        $this->messages['pt'][] = 'O log de erros (^1) não permite escrita pelo usuário web, assim as mensagens devem estar incompletas';
        $this->messages['pt'][] = 'Revise o proprietário do arquivo de log. Ele deve ser igual ao usuário web (geralmente www-data, www, etc)';
        $this->messages['pt'][] = 'Log de erros está vazio ou não foi configurado corretamente. Defina o arquivo de log de erros, configurando <b>error_log</b> no php.ini';
        $this->messages['pt'][] = 'Erros não estão sendo registrados. Por favor, mude <b>log_errors = On</b> no php.ini';
        $this->messages['pt'][] = 'Erros não estão atualmente sendo exibidos por que <b>display_errors</b> está configurado para Off no php.ini';
        $this->messages['pt'][] = 'Esta configuração normalmente é recomendada para produção, não para o propósito de desenvolvimento';
        $this->messages['pt'][] = 'A localização atual do php.ini é <b>^1</b>';
        $this->messages['pt'][] = 'A localização atual do log de erros é <b>^1</b>';
        $this->messages['pt'][] = 'Log do PHP';
        $this->messages['pt'][] = 'Database explorer';
        $this->messages['pt'][] = 'Tabelas';
        $this->messages['pt'][] = 'Relatório gerado. Favor, habilitar os popups';
        $this->messages['pt'][] = 'Arquivo salvo';
        $this->messages['pt'][] = 'Editar página';
        $this->messages['pt'][] = 'Atualizar página';
        $this->messages['pt'][] = 'Módulo';
        $this->messages['pt'][] = 'Diretório';
        $this->messages['pt'][] = 'Código-fonte';
        $this->messages['pt'][] = 'Retorno inválido';
        $this->messages['pt'][] = 'Página';
        $this->messages['pt'][] = 'Falhas na conexão';
        $this->messages['pt'][] = 'Resumo da instalação da base de dados';
        $this->messages['pt'][] = 'Sem permissão de escrita no arquivo';
        $this->messages['pt'][] = 'Para que seja possível continuar com a instalação você deve conceder permissão de leitura para o diretório';
        $this->messages['pt'][] = 'Para que seja possível continuar com a instalação você deve conceder permissão de escrita para o diretório';
        $this->messages['pt'][] = 'Instalada';
        $this->messages['pt'][] = 'Diretório';
        $this->messages['pt'][] = 'Arquivo';
        $this->messages['pt'][] = 'Escrita';
        $this->messages['pt'][] = 'Leitura';
        $this->messages['pt'][] = 'Para que seja possível continuar com a instalação você deve conceder permissão de leitura para o arquivo';
        $this->messages['pt'][] = 'Para que seja possível continuar com a instalação você deve conceder permissão de escrita para o arquivo';
        $this->messages['pt'][] = 'Foto';
        $this->messages['pt'][] = 'Redefinir senha';
        $this->messages['pt'][] = 'Uma nova seed é necessária no application.ini por motivos de segurança';
        $this->messages['pt'][] = 'Troca de senha';
        $this->messages['pt'][] = 'Token expirado. Esta operação não é permitida';
        $this->messages['pt'][] = 'A senha foi alterada';
        $this->messages['pt'][] = 'Um usuário já está cadastrado com este e-mail';
        $this->messages['pt'][] = 'Credenciais LDAP erradas';
        $this->messages['pt'][] = 'Atualizar o menu sobregravando arquivo existente?';
        $this->messages['pt'][] = 'Menu atualizado com sucesso';
        $this->messages['pt'][] = 'Caminho menu';
        $this->messages['pt'][] = 'Adiciona ao menu';
        $this->messages['pt'][] = 'Remove do menu';
        $this->messages['pt'][] = 'Item removido do menu';
        $this->messages['pt'][] = 'Item adicionado ao menu';
        $this->messages['pt'][] = 'Ícone';
        $this->messages['pt'][] = 'Cadastro de usuário';
        $this->messages['pt'][] = 'O cadastro de usuários está desabilitado';
        $this->messages['pt'][] = 'A recuperação de senhas está desabilitada';
        $this->messages['pt'][] = 'Conta criada';
        $this->messages['pt'][] = 'Criar conta';
        $this->messages['pt'][] = 'Se você deseja reinstalar, edite o arquivo app/config/install.ini e altere installed = 1 para installed = 0. Apague o conteúdo no arquivo app/config/install.ini também';
        $this->messages['pt'][] = 'Erro de autorização';
        $this->messages['pt'][] = 'Sair';
        $this->messages['pt'][] = 'Chave REST não definida';
        $this->messages['pt'][] = 'Local';
        $this->messages['pt'][] = 'Remoto';
        $this->messages['pt'][] = 'Sucesso';
        $this->messages['pt'][] = 'Erro';
        $this->messages['pt'][] = 'Status';
        $this->messages['pt'][] = 'Atualiza permissões?';
        $this->messages['pt'][] = 'Modificado';
        $this->messages['pt'][] = 'Adiciona item acima';
        $this->messages['pt'][] = 'Adiciona item abaixo';
        $this->messages['pt'][] = 'Adiciona item filho';
        $this->messages['pt'][] = 'Remover item';
        $this->messages['pt'][] = 'Mover item';
        $this->messages['pt'][] = 'Editor de menu';
        $this->messages['pt'][] = 'Ordenação';
        $this->messages['pt'][] = 'Rótulo';
        $this->messages['pt'][] = 'Cor';
        $this->messages['pt'][] = 'Menu salvo';
        $this->messages['pt'][] = 'Clonar';
        $this->messages['pt'][] = 'Personificar';
        $this->messages['pt'][] = 'Personificado';
        $this->messages['pt'][] = 'Rastreamento da execução';
        $this->messages['pt'][] = 'Sessão';
        $this->messages['pt'][] = 'Log de request';
        $this->messages['pt'][] = 'Método';
        $this->messages['pt'][] = 'Requisição';
        $this->messages['pt'][] = 'Usuários por grupo';
        $this->messages['pt'][] = 'Quantidade';
        $this->messages['pt'][] = 'Usuários por unidade';
        $this->messages['pt'][] = 'Salvar como PDF';
        $this->messages['pt'][] = 'Salvar como CSV';
        $this->messages['pt'][] = 'Salvar como XML';
        $this->messages['pt'][] = 'Exportar';
        $this->messages['pt'][] = 'Informações do sistema';
        $this->messages['pt'][] = 'Memória RAM';
        $this->messages['pt'][] = 'Usando/Total';
        $this->messages['pt'][] = 'Disponível';
        $this->messages['pt'][] = 'Percentual usado';
        $this->messages['pt'][] = 'Uso da CPU';
        $this->messages['pt'][] = 'Fornecedor';
        $this->messages['pt'][] = 'Modelo';
        $this->messages['pt'][] = 'Frequência atual';
        $this->messages['pt'][] = 'Servidor web e processos';
        $this->messages['pt'][] = 'Dispositivos de disco';
        $this->messages['pt'][] = 'Dispositivo';
        $this->messages['pt'][] = 'Ponto de montagem';
        $this->messages['pt'][] = 'Sistema de arquivos';
        $this->messages['pt'][] = 'Dispositivos de rede';
        $this->messages['pt'][] = 'Nome do dispositivo';
        $this->messages['pt'][] = 'Velocidade da porta';
        $this->messages['pt'][] = 'Enviados';
        $this->messages['pt'][] = 'Recebidos';
        $this->messages['pt'][] = 'Imprimir';
        $this->messages['pt'][] = 'Exclui variável de sessão';
        $this->messages['pt'][] = 'Personificado por';
        $this->messages['pt'][] = 'Acesso não autorizado à esta unidade';
        $this->messages['pt'][] = 'Diferença de arquivos';
        $this->messages['pt'][] = 'Removido';
        $this->messages['pt'][] = 'Igual';
        $this->messages['pt'][] = 'Modificado';
        $this->messages['pt'][] = 'Termo de uso e política de privacidade';
        $this->messages['pt'][] = 'Aceitar';
        $this->messages['pt'][] = 'Eu li e concordo com os termos de uso e política de privacidade';
        $this->messages['pt'][] = 'Você precisa ler e concordar com os termos de uso e política de privacidade';
        $this->messages['pt'][] = 'Login realizado em sua conta';
        $this->messages['pt'][] = 'Você acaba de efetuar login com sucesso no ^1. Se não reconhece esse login, entre em contato com o suporte técnico.';
        $this->messages['pt'][] = 'Unidade atual';
        $this->messages['pt'][] = 'Escolha a unidade';
        $this->messages['pt'][] = 'Trocar Unidade';
        $this->messages['pt'][] = 'A unidade é obrigatória';
        $this->messages['pt'][] = 'Unidade alterada!';
        $this->messages['pt'][] = 'Sem permissão para gerenciar este registro!';
        $this->messages['pt'][] = 'Ações';
        $this->messages['pt'][] = 'Atualizar';
        $this->messages['pt'][] = 'Limpar filtros';
        $this->messages['pt'][] = 'Usuário ou senha incorretos';
        $this->messages['pt'][] = 'Configurações de e-mail';
        $this->messages['pt'][] = 'Configurações de segurança';
        $this->messages['pt'][] = 'Habilitar senha forte';
        $this->messages['pt'][] = 'Manutenção';
        $this->messages['pt'][] = 'Modo manutenção';
        $this->messages['pt'][] = 'Mensagem ao tentar realizar o login';
        $this->messages['pt'][] = 'Usuários que podem logar no sistema';
        $this->messages['pt'][] = 'Ao definir as ações, elas se tornam obrigatórias, ou seja o usuário só irá conseguir executar se elas estiverem associadas a um grupo';
        $this->messages['pt'][] = 'Configurar 2FA';
        $this->messages['pt'][] = 'Continuar';
        $this->messages['pt'][] = 'Você precisa selecionar um método 2FA';
        $this->messages['pt'][] = 'Código enviado';
        $this->messages['pt'][] = 'Verificar e ativar';
        $this->messages['pt'][] = 'Código de verificação inválido. Solicite um novo código.';
        $this->messages['pt'][] = 'Código expirado. Solicite um novo código.';
        $this->messages['pt'][] = 'Erro ao enviar email:';
        $this->messages['pt'][] = 'Código de Verificação';
        $this->messages['pt'][] = 'Seu código de verificação é: ^1';
        $this->messages['pt'][] = '2FA por email ativado com sucesso!';
        $this->messages['pt'][] = 'Digite o código de 6 dígitos enviado para seu email';
        $this->messages['pt'][] = 'VERIFICAR';
        $this->messages['pt'][] = 'Verificação em duas etapas';
        $this->messages['pt'][] = 'Digite o código de 6 dígitos do Google Authenticator';
        $this->messages['pt'][] = 'Não recebeu o código?';
        $this->messages['pt'][] = 'Desabilitar';
        $this->messages['pt'][] = "Tem certeza que deseja desabilitar o 2FA ?";
        $this->messages['pt'][] = "2FA desativado com sucesso!";
        $this->messages['pt'][] = "Configurar Autenticação por Email";
        $this->messages['pt'][] = "Confirme seu email";
        $this->messages['pt'][] = "Você receberá os códigos de verificação neste email:";
        $this->messages['pt'][] = "Digite o código";
        $this->messages['pt'][] = "Reenviar código";
        $this->messages['pt'][] = '2FA por Google Authenticator ativado com sucesso!';
        $this->messages['pt'][] = 'Instale o aplicativo';
        $this->messages['pt'][] = 'Escaneie o QR Code';
        $this->messages['pt'][] = 'Ou insira o código manualmente:';
        $this->messages['pt'][] = 'Verifique a configuração';
        $this->messages['pt'][] = 'Digite o código mostrado no Google Authenticator';
        $this->messages['pt'][] = 'Habilitar Google Recaptcha';
        $this->messages['pt'][] = 'Ativar 2FA pelo Google Authenticator';
        $this->messages['pt'][] = 'Ativar 2FA por email';
        $this->messages['pt'][] = 'Assunto do email';
        $this->messages['pt'][] = 'Conteúdo do email';
        $this->messages['pt'][] = 'Variáveis disponíveis:';
        $this->messages['pt'][] = 'Verificação do reCAPTCHA falhou. Tente novamente.';
        $this->messages['pt'][] = '2FA por email não está ativado no sistema';
        $this->messages['pt'][] = '2FA por Google Authenticator não está ativado no sistema';
        $this->messages['pt'][] = 'Tabela de destino';
        $this->messages['pt'][] = 'Campo de destino';
        $this->messages['pt'][] = 'Campo de origem (CSV)';
        $this->messages['pt'][] = 'Mapeamento de colunas';
        $this->messages['pt'][] = 'Importar';
        $this->messages['pt'][] = 'Formato não suportado. Por favor, envie um arquivo CSV.';
        $this->messages['pt'][] = 'Erro ao criar uma cópia do arquivo na pasta tmp. Por favor, verifique as permissões da pasta tmp do seu projeto.';
        $this->messages['pt'][] = 'Nenhum arquivo foi enviado.';
        $this->messages['pt'][] = 'Nenhum mapeamento válido definido.';
        $this->messages['pt'][] = 'Driver não suportado:';
        $this->messages['pt'][] = 'Não foi possível abrir o arquivo CSV.';
        $this->messages['pt'][] = 'Importação realizada com sucesso!<br> Registros processados: ^1';
        $this->messages['pt'][] = 'Configuração em JSON';
        $this->messages['pt'][] = 'Chat interno';
        $this->messages['pt'][] = 'Para utilizar o chat interno é necessário ter informado a configuração em JSON do Firebase.';
        $this->messages['pt'][] = 'Habilitar chat inerno';
        $this->messages['pt'][] = 'Grupos de usuário sem autorização para utilizar o chat';
        $this->messages['pt'][] = 'Usuários sem autorização para utilizar o chat';
        $this->messages['pt'][] = 'Desconectar o usuário?';
        $this->messages['pt'][] = 'Última ação executada';
        $this->messages['pt'][] = 'Desconectar usuário';
        $this->messages['pt'][] = 'Nova conversa';
        $this->messages['pt'][] = 'Iniciar conversa';
        $this->messages['pt'][] = 'Nova conversa em grupo';
        $this->messages['pt'][] = 'Nome do grupo';
        $this->messages['pt'][] = 'Participantes';
        $this->messages['pt'][] = 'Adicionar participantes ao grupo';
        $this->messages['pt'][] = 'Importação de dados';
        $this->messages['pt'][] = 'Monitoramento de usuários';
        $this->messages['pt'][] = 'Navegação';
        $this->messages['pt'][] = 'Restringir o acesso do sistema a uma única aba do navegador';
        $this->messages['pt'][] = 'Segurança';
        $this->messages['pt'][] = 'Permite que os usuários do sistema conversem entre si ou em grupos';
        $this->messages['pt'][] = 'Ao habilitar, apenas os usuários autorizados poderão acessar o sistema';
        $this->messages['pt'][] = 'Senha forte';
        $this->messages['pt'][] = 'Exige que a senha do usuário contenha 8 caracteres, incluindo números, letras maiúsculas, minúsculas e caracteres especiais';
        $this->messages['pt'][] = 'Quando habilitado, adiciona verificação reCAPTCHA na tela de login para proteção contra bots';
        $this->messages['pt'][] = 'Habilitar 2FA por e-mail';
        $this->messages['pt'][] = 'Quando habilitado, os usuários poderão usar 2FA por e-mail';
        $this->messages['pt'][] = 'Habilitar 2FA por Google Authenticator';
        $this->messages['pt'][] = 'Quando habilitado, os usuários poderão usar 2FA por Google Authenticator';
        $this->messages['pt'][] = 'Regras do chat';
        $this->messages['pt'][] = 'Importar dados';

        //fim
        
        $this->messages['es'] = [];
        $this->messages['es'][] = 'Archivo no encontrado';
        $this->messages['es'][] = 'Buscar';
        $this->messages['es'][] = 'Registrar';
        $this->messages['es'][] = 'Registro guardado';
        $this->messages['es'][] = 'Deseas realmente eliminar ?';
        $this->messages['es'][] = 'Registro eliminado';
        $this->messages['es'][] = 'Función';
        $this->messages['es'][] = 'Tabla';
        $this->messages['es'][] = 'Herramienta';
        $this->messages['es'][] = 'Datos';
        $this->messages['es'][] = 'Abrir';
        $this->messages['es'][] = 'Nuevo';
        $this->messages['es'][] = 'Guardar';
        $this->messages['es'][] = 'Buscar';
        $this->messages['es'][] = 'Modificar';
        $this->messages['es'][] = 'Eliminar';
        $this->messages['es'][] = 'Cancelar';
        $this->messages['es'][] = 'Sí';
        $this->messages['es'][] = 'No';
        $this->messages['es'][] = 'Enero';
        $this->messages['es'][] = 'Febrero';
        $this->messages['es'][] = 'Marzo';
        $this->messages['es'][] = 'Abril';
        $this->messages['es'][] = 'Mayo';
        $this->messages['es'][] = 'Junio';
        $this->messages['es'][] = 'Julio';
        $this->messages['es'][] = 'Agosto';
        $this->messages['es'][] = 'Septiembre';
        $this->messages['es'][] = 'Octubre';
        $this->messages['es'][] = 'Noviembre';
        $this->messages['es'][] = 'Diciembre';
        $this->messages['es'][] = 'Hoy';
        $this->messages['es'][] = 'Cerrar';
        $this->messages['es'][] = 'El campo ^1 no puede tener menos de ^2 caracteres';
        $this->messages['es'][] = 'El campo ^1 no puede tener mas de ^2 caracteres';
        $this->messages['es'][] = 'El campo ^1 no puede ser menor que ^2';
        $this->messages['es'][] = 'El campo ^1 no puede ser mayor que ^2';
        $this->messages['es'][] = 'El campo ^1 es obligatorio';
        $this->messages['es'][] = 'El campo ^1 no contiene un CNPJ válido';
        $this->messages['es'][] = 'El campo ^1 no contiene un CPF válido';
        $this->messages['es'][] = 'El campo ^1 contiene um e-mail inválido';
        $this->messages['es'][] = 'Permiso denegado';
        $this->messages['es'][] = 'Generar';
        $this->messages['es'][] = 'Listar';
        $this->messages['es'][] = 'Contraseña incorrecta';
        $this->messages['es'][] = 'Usuário no encontrado';
        $this->messages['es'][] = 'Usuário';
        $this->messages['es'][] = 'Usuários';
        $this->messages['es'][] = 'Contraseña';
        $this->messages['es'][] = 'Login';
        $this->messages['es'][] = 'Nombre';
        $this->messages['es'][] = 'Grupo';
        $this->messages['es'][] = 'Grupos';
        $this->messages['es'][] = 'Programa';
        $this->messages['es'][] = 'Programas';
        $this->messages['es'][] = 'Volver al listado';
        $this->messages['es'][] = 'Classe de control';
        $this->messages['es'][] = 'Email';
        $this->messages['es'][] = 'Registro actualizado';
        $this->messages['es'][] = 'Confirme contraseña';
        $this->messages['es'][] = 'Pantalla inicial';
        $this->messages['es'][] = 'Nombre da la Pantalla';
        $this->messages['es'][] = 'Las contraseñas no conciden';
        $this->messages['es'][] = 'Ingresar';
        $this->messages['es'][] = 'Fecha';
        $this->messages['es'][] = 'Columna';
        $this->messages['es'][] = 'Operación';
        $this->messages['es'][] = 'Valor anterior';
        $this->messages['es'][] = 'Valor nuevo';
        $this->messages['es'][] = 'Base de datos';
        $this->messages['es'][] = 'Perfil';
        $this->messages['es'][] = 'Cambiar contraseña';
        $this->messages['es'][] = 'Deje vacio para mantener la contraseña anterior';
        $this->messages['es'][] = 'Resultados';
        $this->messages['es'][] = 'Comando inválido';
        $this->messages['es'][] = '^1 registros  exhibidos';
        $this->messages['es'][] = 'Administración';
        $this->messages['es'][] = 'Panel SQL';
        $this->messages['es'][] = 'Log de acceso';
        $this->messages['es'][] = 'Log de modificaciones';
        $this->messages['es'][] = 'Log de SQL';
        $this->messages['es'][] = 'Limpiar formulário';
        $this->messages['es'][] = 'Enviar';
        $this->messages['es'][] = 'Mensaje';
        $this->messages['es'][] = 'Mensajes';
        $this->messages['es'][] = 'Asunto';
        $this->messages['es'][] = 'Mensaje enviada exitosamente';
        $this->messages['es'][] = 'Marcar como leída';
        $this->messages['es'][] = 'Marcar como no leída';
        $this->messages['es'][] = 'Acción';
        $this->messages['es'][] = 'Leer';
        $this->messages['es'][] = 'Origen';
        $this->messages['es'][] = 'Verificado';
        $this->messages['es'][] = 'Objeto ^1 no encontrado en ^2';
        $this->messages['es'][] = 'Notificación';
        $this->messages['es'][] = 'Notificaciones';
        $this->messages['es'][] = 'Categorias';
        $this->messages['es'][] = 'Enviar documentos';
        $this->messages['es'][] = 'Mis documentos';
        $this->messages['es'][] = 'Compartidos conmigo';
        $this->messages['es'][] = 'Documento';
        $this->messages['es'][] = 'Archivo';
        $this->messages['es'][] = 'Título';
        $this->messages['es'][] = 'Descripción';
        $this->messages['es'][] = 'Categoria';
        $this->messages['es'][] = 'Fecha de envio';
        $this->messages['es'][] = 'Fecha de archivamiento';
        $this->messages['es'][] = 'Upload';
        $this->messages['es'][] = 'Download';
        $this->messages['es'][] = 'Siguiente';
        $this->messages['es'][] = 'Documentos';
        $this->messages['es'][] = 'Permiso';
        $this->messages['es'][] = 'Unidad';
        $this->messages['es'][] = 'Unidades';
        $this->messages['es'][] = 'Agrega';
        $this->messages['es'][] = 'Activo';
        $this->messages['es'][] = 'Activar/desactivar';
        $this->messages['es'][] = 'Usuário desactivado';
        $this->messages['es'][] = 'Envia mensaje';
        $this->messages['es'][] = 'Leer mensaje';
        $this->messages['es'][] = 'Un usuário ya está registrado con este login';
        $this->messages['es'][] = 'Estadisticas de acceso';
        $this->messages['es'][] = 'Accesos';
        $this->messages['es'][] = 'Preferencias';
        $this->messages['es'][] = 'E-mail de origen';
        $this->messages['es'][] = 'Autentica SMTP';
        $this->messages['es'][] = 'Host SMTP';
        $this->messages['es'][] = 'Puerta SMTP';
        $this->messages['es'][] = 'Usuário SMTP';
        $this->messages['es'][] = 'Contraseña SMTP';
        $this->messages['es'][] = 'Ticket';
        $this->messages['es'][] = 'Abrir ticket';
        $this->messages['es'][] = 'Email de soporte';
        $this->messages['es'][] = 'Dia';
        $this->messages['es'][] = 'Carpetas';
        $this->messages['es'][] = 'Componer';
        $this->messages['es'][] = 'Entrada';
        $this->messages['es'][] = 'Enviados';
        $this->messages['es'][] = 'Archivados';
        $this->messages['es'][] = 'Archivar';
        $this->messages['es'][] = 'Recuperar';
        $this->messages['es'][] = 'Valor';
        $this->messages['es'][] = 'Ver todos';
        $this->messages['es'][] = 'Recargar';
        $this->messages['es'][] = 'Volver';
        $this->messages['es'][] = 'Limpiar';
        $this->messages['es'][] = 'Visualizar';
        $this->messages['es'][] = 'Ningun registro fue encontrado';
        $this->messages['es'][] = 'Diseño generado con éxito';
        $this->messages['es'][] = 'QR codes generados con éxito';
        $this->messages['es'][] = 'Códigos de barra generados con éxito';
        $this->messages['es'][] = 'Documento generado con éxito';
        $this->messages['es'][] = 'Valor';
        $this->messages['es'][] = 'Usuário';
        $this->messages['es'][] = 'Contraseña';
        $this->messages['es'][] = 'Puerta';
        $this->messages['es'][] = 'Tipo de base de datos';
        $this->messages['es'][] = 'Usuário admin';
        $this->messages['es'][] = 'Contraseña del usuário admin';
        $this->messages['es'][] = 'Crear base de datos/usuário';
        $this->messages['es'][] = 'Testear conexión';
        $this->messages['es'][] = 'Nombree de la base de datos';
        $this->messages['es'][] = 'Ingresar permisos/programas';
        $this->messages['es'][] = 'Base de datos y usuário creado con éxito';
        $this->messages['es'][] = 'Permisos y programas agregados con éxito';
        $this->messages['es'][] = 'Actualizar configuración';
        $this->messages['es'][] = 'Archivo de configuración: ^1 actualizado con éxito';
        $this->messages['es'][] = 'Conexión realizada con éxito';
        $this->messages['es'][] = 'La base de datos ^1 no existe';
        $this->messages['es'][] = 'Permisos/programas agregados con éxito';
        $this->messages['es'][] = 'Los programas/permisos ya fueron agregados';
        $this->messages['es'][] = 'Instalando en su aplicación';
        $this->messages['es'][] = 'Verficación de la version del PHP y extenciones instaladas';
        $this->messages['es'][] = 'Verficación del PHP';
        $this->messages['es'][] = 'Verficación de directorios y archivos';
        $this->messages['es'][] = 'Configuración/creación de la base de datos';
        $this->messages['es'][] = 'Usuário admin';
        $this->messages['es'][] = 'Contraseña del usuário admin';
        $this->messages['es'][] = 'Ingresar datos';
        $this->messages['es'][] = 'De la base de datos:';
        $this->messages['es'][] = 'La conexión con la base de datos ^1 falló';
        $this->messages['es'][] = 'Instalar';
        $this->messages['es'][] = 'Bases de datos instaladas con éxito';
        $this->messages['es'][] = 'Las bases de datos ya fueron instaladas';
        $this->messages['es'][] = 'Unidad principal';
        $this->messages['es'][] = 'Hora';
        $this->messages['es'][] = 'Tipo';
        $this->messages['es'][] = 'Falla al leer el log de errores (^1)';
        $this->messages['es'][] = 'El log de errores (^1) no permite escritura por el usuário web, así que los mensajes deben estar incompletos';
        $this->messages['es'][] = 'Revise el propietario del archivo de log. Debe ser igual al usuário web (generalmente www-data, www, etc)';
        $this->messages['es'][] = 'Log de errores está vacio o no fue configurado correctamente. Defina el archivo de log de errores, configurando <b>error_log</b> en el php.ini';
        $this->messages['es'][] = 'Errores no estan siendo registrados. Por favor, modifique <b>log_errors = On</b> en el php.ini';
        $this->messages['es'][] = 'Errores no estan actualmente siendo mostrados porque <b>display_errors</b> está configurado para Off en el php.ini';
        $this->messages['es'][] = 'Esta configuración normalmente es recomendada para producción, no para el propósito de desarrollo';
        $this->messages['es'][] = 'La ubicación actual del php.ini es <b>^1</b>';
        $this->messages['es'][] = 'La ubicación actual del log de errores es <b>^1</b>';
        $this->messages['es'][] = 'Log del PHP';
        $this->messages['es'][] = 'Database explorer';
        $this->messages['es'][] = 'Tablas';
        $this->messages['es'][] = 'Reporte generado. Favor, habilitar los popups';
        $this->messages['es'][] = 'Archivo guardado';
        $this->messages['es'][] = 'Modificar página';
        $this->messages['es'][] = 'Actualizar página';
        $this->messages['es'][] = 'Módulo';
        $this->messages['es'][] = 'Directório';
        $this->messages['es'][] = 'Código-fuente';
        $this->messages['es'][] = 'Retorno inválido';
        $this->messages['es'][] = 'Página';
        $this->messages['es'][] = 'Fallas en la conexión';
        $this->messages['es'][] = 'Resumen de la instalación de la base de datos';
        $this->messages['es'][] = 'Sin permiso de escritura en el archivo';
        $this->messages['es'][] = 'Para que sea posible continuar con la instalación usted debe conceder permisos de lectura para el directório';
        $this->messages['es'][] = 'Para que sea posible continuar con la instalación usted debe conceder permisos de escritura para el directório';
        $this->messages['es'][] = 'Instalada';
        $this->messages['es'][] = 'Directório';
        $this->messages['es'][] = 'Archivo';
        $this->messages['es'][] = 'Escritura';
        $this->messages['es'][] = 'Lectura';
        $this->messages['es'][] = 'Para que sea posible continuar con la instalación usted debe conceder permisos de lectura para el archivo';
        $this->messages['es'][] = 'Para que sea posible continuar con la instalación usted debe conceder permisos de escritura para el archivo';
        $this->messages['es'][] = 'Foto';
        $this->messages['es'][] = 'Cambiar contraseña';
        $this->messages['es'][] = 'Una nueva seed es necesaria en application.ini por motivos de seguridad';
        $this->messages['es'][] = 'Cambiar la contraseña';
        $this->messages['es'][] = 'Token expirado. Esta operación no está permitida';
        $this->messages['es'][] = 'La contraseña fue modificada';
        $this->messages['es'][] = 'Un usuário ya está registrado con este e-mail';
        $this->messages['es'][] = 'Credenciales LDAP incorrectas';
        $this->messages['es'][] = 'Actualizar el menu reemplazando el archivo existente?';
        $this->messages['es'][] = 'Menu actualizado con éxito';
        $this->messages['es'][] = 'Dirección del menu';
        $this->messages['es'][] = 'Agregar al menu';
        $this->messages['es'][] = 'Eliminar del menu';
        $this->messages['es'][] = 'Iten eliminado del menu';
        $this->messages['es'][] = 'Iten agregado al menu';
        $this->messages['es'][] = 'Ícono';
        $this->messages['es'][] = 'Registro de usuário';
        $this->messages['es'][] = 'El registro de usuários está desactivado';
        $this->messages['es'][] = 'La recuperación de contraseña está desactivada';
        $this->messages['es'][] = 'Cuenta creada';
        $this->messages['es'][] = 'Crear cuenta';
        $this->messages['es'][] = 'Si desea reinstalar, edite el archivo app/config/install.ini y cambie installed = 1 a installed = 0. Borre el contenido en el archivo app/config/install.ini también';
        $this->messages['es'][] = 'Error de autorización';
        $this->messages['es'][] = 'Salir';
        $this->messages['es'][] = 'Clave REST no definida';
        $this->messages['es'][] = 'Local';
        $this->messages['es'][] = 'Remoto';
        $this->messages['es'][] = 'Éxito';
        $this->messages['es'][] = 'Error';
        $this->messages['es'][] = 'Estado';
        $this->messages['es'][] = 'Actualizar permisos?';
        $this->messages['es'][] = 'Cambiado';
        $this->messages['es'][] = 'Agregar elemento arriba';
        $this->messages['es'][] = 'Agregar elemento abajo';
        $this->messages['es'][] = 'Adiciona item hijo';
        $this->messages['es'][] = 'Excluir item';
        $this->messages['es'][] = 'Mover elemento';
        $this->messages['es'][] = 'Editor de menú';
        $this->messages['es'][] = 'Ordenación';
        $this->messages['es'][] = 'Etiqueta';
        $this->messages['es'][] = 'Color';
        $this->messages['es'][] = 'Menú guardado';
        $this->messages['es'][] = 'Clonar';
        $this->messages['es'][] = 'Personificar';
        $this->messages['es'][] = 'Personificado';
        $this->messages['es'][] = 'Rastreo de ejecución';
        $this->messages['es'][] = 'Sesión';
        $this->messages['es'][] = 'Log de request';
        $this->messages['es'][] = 'Método';
        $this->messages['es'][] = 'Request';
        $this->messages['es'][] = 'Usuarios por grupo';
        $this->messages['es'][] = 'Cantidad';
        $this->messages['es'][] = 'Usuarios por unidad';
        $this->messages['es'][] = 'Guardar como PDF';
        $this->messages['es'][] = 'Guardar como CSV';
        $this->messages['es'][] = 'Guardar como XML';
        $this->messages['es'][] = 'Exportar';
        $this->messages['es'][] = 'Informaciones del sistema';
        $this->messages['es'][] = 'Memória RAM';
        $this->messages['es'][] = 'Utilizando/Total';
        $this->messages['es'][] = 'Disponible';
        $this->messages['es'][] = 'Porcentaje utilizado';
        $this->messages['es'][] = 'Uso de CPU';
        $this->messages['es'][] = 'Proveedor';
        $this->messages['es'][] = 'Modelo';
        $this->messages['es'][] = 'Frecuencia actual';
        $this->messages['es'][] = 'Servidor web y procesos';
        $this->messages['es'][] = 'Dispositivos de disco';
        $this->messages['es'][] = 'Dispositivo';
        $this->messages['es'][] = 'Punto de montaje';
        $this->messages['es'][] = 'Sistema de archivos';
        $this->messages['es'][] = 'Dispositivos de red';
        $this->messages['es'][] = 'Nombre del dispositivo';
        $this->messages['es'][] = 'Velocidad de la puerta';
        $this->messages['es'][] = 'Enviados';
        $this->messages['es'][] = 'Recebidos';
        $this->messages['es'][] = 'Imprimir';
        $this->messages['es'][] = 'Eliminar variable de sesión';
        $this->messages['es'][] = 'Personificado por';
        $this->messages['es'][] = 'Acceso prohibido a esta unidad';
        
        $this->messages['es'][] = 'Diferencia de archivo';
        $this->messages['es'][] = 'Remoto';
        $this->messages['es'][] = 'Igual';
        $this->messages['es'][] = 'Cambiado';
        $this->messages['es'][] = 'Términos de uso y política de privacidad';
        $this->messages['es'][] = 'Aceptar';
        $this->messages['es'][] = 'He leído y acepto los términos de uso y la política de privacidad';
        $this->messages['es'][] = 'Necesita leer y aceptar los términos de uso y la política de privacidad';
        $this->messages['es'][] = 'Ingrese a su cuenta';
        $this->messages['es'][] = 'Acaba de iniciar sesión correctamente en ^1. Si no reconoce este inicio de sesión, comuníquese con el soporte técnico';
        $this->messages['es'][] = 'Unidad atual';
        $this->messages['es'][] = 'Elige la unidad';
        $this->messages['es'][] = 'Cambiar Unidad';
        $this->messages['es'][] = 'La unidad es obligatoria';
        $this->messages['es'][] = '¡Unidad cambiada!';
        $this->messages['es'][] = '¡Sin permiso para gestionar este registro!';
        $this->messages['es'][] = 'Accións';
        $this->messages['es'][] = 'Actualizar';
        $this->messages['es'][] = 'Borrar filtros';
        $this->messages['es'][] = 'Usuario o contraseña incorrectos';
        $this->messages['es'][] = 'Configuraciones de correo electrónico';
        $this->messages['es'][] = 'Configuraciones de seguridad';
        $this->messages['es'][] = 'Contraseña fuerte';
        $this->messages['es'][] = 'Mantenimiento';
        $this->messages['es'][] = 'Modo mantenimiento';
        $this->messages['es'][] = 'Mensaje al intentar iniciar sesión"';
        $this->messages['es'][] = 'Usuarios que pueden iniciar sesión en el sistema';
        $this->messages['es'][] = 'Al definir las acciones, estas se vuelven obligatorias, es decir, el usuario solo podrá ejecutarlas si están asociadas a un grupo';
        $this->messages['es'][] = 'Configurar 2FA';
        $this->messages['es'][] = 'Continuar';
        $this->messages['es'][] = 'Necesitas seleccionar un método 2FA';
        $this->messages['es'][] = 'Código enviado';
        $this->messages['es'][] = 'Verificar y activar';
        $this->messages['es'][] = 'Código de verificación inválido. Solicita un nuevo código.';
        $this->messages['es'][] = 'Código expirado. Solicita un nuevo código.';
        $this->messages['es'][] = 'Error al enviar correo:';
        $this->messages['es'][] = 'Código de verificación';
        $this->messages['es'][] = 'Tu código de verificación es: ^1';
        $this->messages['es'][] = '¡2FA por correo activado exitosamente!';
        $this->messages['es'][] = 'Ingresa el código de 6 dígitos enviado a tu correo';
        $this->messages['es'][] = 'VERIFICAR';
        $this->messages['es'][] = 'Verificación en dos pasos';
        $this->messages['es'][] = 'Ingresa el código de 6 dígitos de Google Authenticator';
        $this->messages['es'][] = "¿No has recibido el código?";
        $this->messages['es'][] = "Deshabilitar";
        $this->messages['es'][] = "¿Realmente deseas deshabilitar 2FA?";
        $this->messages['es'][] = "¡2FA deshabilitado exitosamente!";
        $this->messages['es'][] = "Configurar Autenticación por Correo";
        $this->messages['es'][] = "Confirma tu correo";
        $this->messages['es'][] = "Recibirás códigos de verificación en este correo:";
        $this->messages['es'][] = "Ingresa el código";
        $this->messages['es'][] = "Reenviar código";
        $this->messages['es'][] = "¡2FA por Google Authenticator activado exitosamente!";
        $this->messages['es'][] = "Instalar la aplicación";
        $this->messages['es'][] = 'Escanea el código QR';
        $this->messages['es'][] = 'O ingresa el código manualmente:';
        $this->messages['es'][] = 'Verifica la configuración';
        $this->messages['es'][] = 'Ingresa el código mostrado en Google Authenticator';
        $this->messages['es'][] = 'Habilitar Google reCAPTCHA';
        $this->messages['es'][] = 'Habilitar 2FA por Google Authenticator';
        $this->messages['es'][] = 'Habilitar 2FA por correo';
        $this->messages['es'][] = 'Asunto del correo';
        $this->messages['es'][] = 'Contenido del correo';
        $this->messages['es'][] = 'Variables disponibles:';
        $this->messages['es'][] = 'Verificación reCAPTCHA fallida. Por favor intenta de nuevo.';
        $this->messages['es'][] = 'El 2FA por correo no está habilitado en el sistema';
        $this->messages['es'][] = 'El 2FA por Google Authenticator no está habilitado en el sistema';
        $this->messages['es'][] = 'Tabla de destino';
        $this->messages['es'][] = 'Campo de destino';
        $this->messages['es'][] = 'Campo de origen (CSV)';
        $this->messages['es'][] = 'Mapeo de columnas';
        $this->messages['es'][] = 'Importar';
        $this->messages['es'][] = 'Formato no soportado. Por favor, suba un archivo CSV.';
        $this->messages['es'][] = 'Error al crear una copia del archivo en la carpeta tmp. Por favor, verifique los permisos de la carpeta tmp de su proyecto.';
        $this->messages['es'][] = 'Ningún archivo fue subido.';
        $this->messages['es'][] = 'Ningún mapeo válido definido.';
        $this->messages['es'][] = 'Controlador no soportado:';
        $this->messages['es'][] = 'No se pudo abrir el archivo CSV.';
        $this->messages['es'][] = '¡Importación realizada con éxito!<br> Registros procesados: ^1';
        $this->messages['es'][] = 'Configuración en JSON';
        $this->messages['es'][] = 'Chat interno';
        $this->messages['es'][] = 'Para utilizar el chat interno, es necesario haber proporcionado la configuración JSON de Firebase.';
        $this->messages['es'][] = 'Habilitar chat interno';
        $this->messages['es'][] = 'Grupos de usuarios no autorizados para usar el chat';
        $this->messages['es'][] = 'Usuarios no autorizados para usar el chat';
        $this->messages['es'][] = '¿Desconectar al usuario?';
        $this->messages['es'][] = 'Última acción ejecutada';
        $this->messages['es'][] = 'Desconectar usuario';
        $this->messages['es'][] = 'Nueva conversación';
        $this->messages['es'][] = 'Iniciar conversación';
        $this->messages['es'][] = 'Nueva conversación grupal';
        $this->messages['es'][] = 'Nombre del grupo';
        $this->messages['es'][] = 'Participantes';
        $this->messages['es'][] = 'Añadir participantes al grupo';
        $this->messages['es'][] = 'Importación de datos';
        $this->messages['es'][] = 'Supervisión de usuarios';
        $this->messages['es'][] = 'Navegación';
        $this->messages['es'][] = 'Restringir el acceso del sistema a una única pestaña del navegador';
        $this->messages['es'][] = 'Seguridad';
        $this->messages['es'][] = 'Permite que los usuarios del sistema conversen entre sí o en grupos';
        $this->messages['es'][] = 'Cuando está habilitado, solo los usuarios autorizados podrán acceder al sistema';
        $this->messages['es'][] = 'Contraseña fuerte';
        $this->messages['es'][] = 'Requiere que la contraseña del usuario contenga 8 caracteres, incluyendo números, letras mayúsculas, minúsculas y caracteres especiales';
        $this->messages['es'][] = 'Cuando está habilitado, añade verificación reCAPTCHA en la pantalla de inicio de sesión para protección contra bots';
        $this->messages['es'][] = 'Habilitar 2FA por correo electrónico';
        $this->messages['es'][] = 'Cuando está habilitado, los usuarios podrán usar 2FA por correo electrónico';
        $this->messages['es'][] = 'Habilitar 2FA por Google Authenticator';
        $this->messages['es'][] = 'Cuando está habilitado, los usuarios podrán usar 2FA por Google Authenticator';
        $this->messages['es'][] = 'Reglas del chat';
        $this->messages['es'][] = 'Importar datos';


        
        foreach ($this->messages as $lang => $messages)
        {
            $this->sourceMessages[$lang] = array_flip( $this->messages[ $lang ] );
        }
    }
    
    /**
     * Returns the singleton instance
     * @return  Instance of self
     */
    public static function getInstance()
    {
        // if there's no instance
        if (empty(self::$instance))
        {
            // creates a new object
            self::$instance = new self;
        }
        // returns the created instance
        return self::$instance;
    }
    
    /**
     * Define the target language
     * @param $lang     Target language index
     */
    public static function setLanguage($lang, $global = true)
    {
        $instance = self::getInstance();
        if (in_array($lang, array_keys($instance->messages)))
        {
            $instance->lang = $lang;
        }
        
        if ($global)
        {
            AdiantiCoreTranslator::setLanguage( $lang );
        }
    }
    
    /**
     * Returns the target language
     * @return Target language index
     */
    public static function getLanguage()
    {
        $instance = self::getInstance();
        return $instance->lang;
    }
    
    /**
     * Translate a word to the target language
     * @param $word     Word to be translated
     * @return          Translated word
     */
    public static function translate($word, $source_language, $param1 = NULL, $param2 = NULL, $param3 = NULL)
    {
        // get the self unique instance
        $instance = self::getInstance();
        // search by the numeric index of the word
        
        if (isset($instance->sourceMessages[$source_language][$word]) and !is_null($instance->sourceMessages[$source_language][$word]))
        {
            $key = $instance->sourceMessages[$source_language][$word]; //$key = array_search($word, $instance->messages['en']);
            
            // get the target language
            $language = self::getLanguage();
            // returns the translated word
            $message = $instance->messages[$language][$key];
            
            if (isset($param1))
            {
                $message = str_replace('^1', $param1, $message);
            }
            if (isset($param2))
            {
                $message = str_replace('^2', $param2, $message);
            }
            if (isset($param3))
            {
                $message = str_replace('^3', $param3, $message);
            }
            return $message;
        }
        else
        {
            return 'Message not found: '. $word;
        }
    }
    
    /**
     * Translate a template file
     */
    public static function translateTemplate($template)
    {
        // search by translated words
        if(preg_match_all( '!_t\{(.*?)\}!i', $template, $match ) > 0)
        {
            foreach($match[1] as $word)
            {
                $translated = _t($word);
                $template = str_replace('_t{'.$word.'}', $translated, $template);
            }
        }
        
        if(preg_match_all( '!_tf\{(.*?),\s*(.*?)\}!i', $template, $matches ) > 0)
        {
            foreach($matches[0] as $key => $match)
            {
                $raw        = $matches[0][$key];
                $word       = $matches[1][$key];
                $from       = $matches[2][$key];
                $translated = _tf($word, $from);
                $template = str_replace($raw, $translated, $template);
            }
        }
        return $template;
    }
}

/**
 * Facade to translate words from english
 * @param $word  Word to be translated
 * @param $param1 optional ^1
 * @param $param2 optional ^2
 * @param $param3 optional ^3
 * @return Translated word
 */
function _t($msg, $param1 = null, $param2 = null, $param3 = null)
{
    return ApplicationTranslator::translate($msg, 'en', $param1, $param2, $param3);
}

/**
 * Facade to translate words from specified language
 * @param $word  Word to be translated
 * @param $source_language  Source language
 * @param $param1 optional ^1
 * @param $param2 optional ^2
 * @param $param3 optional ^3
 * @return Translated word
 */
function _tf($msg, $source_language = 'en', $param1 = null, $param2 = null, $param3 = null)
{
    return ApplicationTranslator::translate($msg, $source_language, $param1, $param2, $param3);
}
