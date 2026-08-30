<?php

/**
 * TwoFactorService
 * Service class to handle email-based 2FA authentication
 *
 * @version    1.0
 * @package    service
 * @subpackage auth
 */
class TwoFactorEmailService
{
    private static $codeExpiration = 600; // 10 minutes in seconds
    
    /**
     * Generate and send email verification code
     * @param string $email User email
     * @param string $name User name
     * @return string Generated code
     */
    public static function generateAndSendEmailCode($email, $name)
    {
        try {
            // Generate a 6-digit code
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store code in session with timestamp
            TSession::setValue('2fa_email_code', [
                'code' => $code,
                'timestamp' => time(),
                'email' => $email
            ]);
            
            // Get email preferences
            $preferences = SystemPreference::getAllPreferences();
            
            $title = $preferences['2fa_email_subject'] ?? _t('Verification code');
            $content = $preferences['2fa_email_content'] ?? _t('Your verification code is: ^1', $code);
            
            // Replace placeholders
            $content = str_replace(['{$code}', '{$name}'],[$code, $name], $content );
            
            // Send email using MailService
            MailService::send($email, $title, $content, 'html');
            
            return true;
        }
        catch (Exception $e) {
            throw new Exception(_t("Error sending email:: ") . $e->getMessage());
        }
    }
    
    /**
     * Verify email code
     * @param string $code Code to verify
     * @return bool
     */
    public static function verifyEmailCode($code)
    {
        $stored = TSession::getValue('2fa_email_code');
        
        if (empty($stored)) {
            return false;
        }
        
        // Check if code has expired
        if ((time() - $stored['timestamp']) > self::$codeExpiration) {
            TSession::delValue('2fa_email_code');
        }
        
        return $stored['code'] === $code;
    }
}
