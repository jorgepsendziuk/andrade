<?php

class BuilderPermissionService{
    
    public static function checkPermission()
    {
        if (TSession::getValue('login') !== 'admin')
        {
            throw new Exception(_bt('Permission denied'));
        }
    }

    public static function canManageRecordByUnit($record, $hook = null)
    {
        $unit_column_name = $record->getCreatedByUnitIdColumn();
    
        if($unit_column_name && TSession::getValue('userunitids'))
        {
            $pk = $record->getPrimaryKey();
            
            if (($record->{$unit_column_name} && !in_array($record->{$unit_column_name}, TSession::getValue('userunitids'))) || ($record->{$pk} && isset($record->{$unit_column_name}) && !$record->{$unit_column_name}))
            {
                throw new Exception(_t('No permission to manage this record!'));
            }
        }
    }

    public static function verifyHasPermission(TAction $action)
    {
        $action = $action->toString();
        $action = explode('::', $action);
        $class = null;
        
        if(count($action) > 1)
        {
            $class = $action[0];
            $method = $action[1];
        }

        $programs_actions = TSession::getValue('programs_actions');
        $programs = TSession::getValue('programs');

        $ini = AdiantiApplicationConfig::get();
        if(in_array($class, $ini['permission']['public_classes']))
        {
            return true;
        }

        $defaultProgramsPermissions = TApplication::getDefaultPermissions();

        if(!empty($defaultProgramsPermissions[$class]))
        {
            return true;
        }

        if( !isset($programs[$class]) || ($class && $method && isset($programs_actions[$class][$method]) && $programs_actions[$class][$method] == false))
        {
            return false;
        }

        return true;
    }
}