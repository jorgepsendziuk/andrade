<?php

class BuilderFirebaseService 
{
    private static function getServiceAccount() 
    {
        $serviceAccount = json_decode(SystemPreferenceService::getFirebaseJson(), true);
        if (!$serviceAccount) 
        {
            throw new Exception('Failed to load service account configuration');
        }
        return $serviceAccount;
    }

    private static function getAccessToken() 
    {
        $serviceAccount = self::getServiceAccount();
        
        $now = time();
        $payload = [
            "iss" => $serviceAccount['client_email'],
            "scope" => "https://www.googleapis.com/auth/firebase.database https://www.googleapis.com/auth/userinfo.email",
            "aud" => "https://oauth2.googleapis.com/token",
            "exp" => $now + 3600,
            "iat" => $now
        ];

        $key = $serviceAccount['private_key'];
        
        $jwt = self::createJWT($payload, $key);
        
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) 
        {
            throw new Exception('Failed to get access token');
        }

        return $data['access_token'];
    }

    private static function createJWT($payload, $key) 
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'RS256'
        ];

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signatureInput = $base64UrlHeader . "." . $base64UrlPayload;
        openssl_sign($signatureInput, $signature, $key, OPENSSL_ALGO_SHA256);
        
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public static function createUserToken() 
    {
        try 
        {
            $serviceAccount = self::getServiceAccount();
            $now = time();
            
            $payload = [
                "iss" => $serviceAccount['client_email'],
                "sub" => $serviceAccount['client_email'],
                "aud" => "https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit",
                "iat" => $now,
                "exp" => $now + 3600,
                "uid" => TSession::getValue('userid'),
                // "uid" => self::createUserIdHash(TSession::getValue('userid')),
            ];

            return self::createJWT($payload, $serviceAccount['private_key']);
        } 
        catch (Exception $e) 
        {
            throw new Exception('Failed to create user token: ' . $e->getMessage());
        }
    }

    private static function getFirebaseUrl() 
    {
        $serviceAccount = self::getServiceAccount();
        return "https://{$serviceAccount['project_id']}-default-rtdb.firebaseio.com";
    }

    public static function getUsers() 
    {
        try 
        {
            $accessToken = self::getAccessToken();
            $baseUrl = self::getFirebaseUrl();
            
            $ch = curl_init($baseUrl . '/users.json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken
            ]);

            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            
            curl_close($ch);
            
            return json_decode($response, true);
        } 
        catch (Exception $e) 
        {
            throw new Exception('Failed to get users: ' . $e->getMessage());
        }
    }

    public static function setUserAttribute($userId, $attributeName, $attributeValue) 
    {
        try 
        {
            $accessToken = self::getAccessToken();
            
            // $sanitizedUserId = self::createUserIdHash($userId);
            $sanitizedUserId = $userId;
            $baseUrl = self::getFirebaseUrl();
            $ch = curl_init($baseUrl . "/users/{$sanitizedUserId}/{$attributeName}.json");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($attributeValue));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }
            
            curl_close($ch);
            
            return true;
        } 
        catch (Exception $e) 
        {
            echo "Erro ao atualizar o atributo $attributeName para o usuário $userId: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public static function createUserIdHash($userId) 
    {
        $configs = AdiantiApplicationConfig::get();
        return hash('sha256', $userId.$configs['general']['token']); 
    }
    
    public static function chatExists($user1Id, $user2Id)
    {
        try {
            $accessToken = self::getAccessToken();
            $baseUrl = self::getFirebaseUrl();
            
            // Primeiro tenta com user1 como primeiro participante
            $query = "/rooms.json?orderBy=\"participants/$user1Id\"&equalTo=true";
            
            $ch = curl_init($baseUrl . $query);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken
            ]);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception(curl_error($ch));
            }

            curl_close($ch);
            $data = json_decode($response, true);
            
            if (!empty($data)) {
                foreach ($data as $room) {
                    if (isset($room['type']) && 
                        $room['type'] === 'chat' && 
                        isset($room['participants'][$user2Id]) && 
                        $room['participants'][$user2Id] === true &&
                        count($room['participants']) === 2) {
                        return true;
                    }
                }
            }

            return false;
        } catch (Exception $e) {
            throw new Exception('Failed to check if chat exists: ' . $e->getMessage());
        }
    }
}