<?php

use Adianti\Registry\TSession;

class SystemChatService
{
    public static function getUserItems()
    {
        $users = self::getUsers();
        $items = [];
        if($users)
        {
            foreach($users as $user)
            {
                // $items[BuilderFirebaseService::createUserIdHash($user->id)] = $user->name;
                $items[$user->id] = $user->name;
            }
        }

        return $items;
    }

    public static function getUsers()
    {
        $criteria = self::getUsersCriteria();

        return SystemUsers::getObjects($criteria);
    }

    public static function getUsersCriteria()
    {
        $criteria = new TCriteria();
        $criteria->add(new TFilter('id', '!=', TSession::getValue('userid')));
        $criteria->add(new TFilter('active', '=', 'Y'));

        if(TSession::getValue('userunitids'))
        {
            $units = implode(',', TSession::getValue('userunitids'));
            $criteria->add(new TFilter('id', 'in', "(SELECT system_user_id FROM system_user_unit WHERE system_unit_id in ($units))"));
        }

        $preferences = SystemPreferenceService::getPreferences();

        if(!empty($preferences['internal_chat_unauthorized_groups']))
        {
            $groups = $preferences['internal_chat_unauthorized_groups'];
            $criteria->add(new TFilter('id', 'not in', "(SELECT system_user_id FROM system_user_group WHERE system_group_id in ($groups))"));
        }
        
        if(!empty($preferences['internal_chat_unauthorized_users']))
        {
            $users = $preferences['internal_chat_unauthorized_users'];
            $criteria->add(new TFilter('id', 'not in', $users));
        }
        
        return $criteria;
    }

    public static function isEnabled()
    {
        $preferences = SystemPreferenceService::getPreferences();

        if(!empty($preferences['internal_chat']) && $preferences['internal_chat'] == 'T' && !empty($preferences['firebase_json']))
        {
            return true;
        }
        
        return false;
    }

}