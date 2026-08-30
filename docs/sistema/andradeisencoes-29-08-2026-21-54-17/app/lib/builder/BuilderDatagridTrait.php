<?php
use Adianti\Registry\TSession;
use Adianti\Database\TTransaction;

/**
 * Datagrid Trait
 *
 * Provides methods for managing datagrid properties.
 *
 * @version    4.0
 * @package    base
 * @author     Matheus Agnes Dias
 * @copyright  Copyright (c) 2025 www.madbuilder.com.br 
 */
trait BuilderDatagridTrait
{
    public function applyDatagridProperties()
    {

        $datagridProperties = TSession::getValue(__CLASS__.'_datagrid_properties');

        if(!$datagridProperties)
        {
            TTransaction::open('permission');
            
            $preferenceKey = TSession::getValue('userid').'_user_datagrid_properties';
            $preference = SystemPreference::find($preferenceKey);

            if($preference)
            {
                $datagridProperties = json_decode($preference->preference, true) ?? [];
                if(!empty($datagridProperties[__CLASS__]))
                {
                    $datagridProperties = $datagridProperties[__CLASS__];
                }
            }

            TTransaction::close();
        }

        if($datagridProperties)
        {
            if(is_array($datagridProperties))
            {
                $datagridProperties = (object) $datagridProperties;
            }
            
            $this->datagrid->unhideColumns();

            if(!empty($datagridProperties->hideColumns))
            {
                $this->datagrid->setHideColumns($datagridProperties->hideColumns);
            }
            
            if(!empty($datagridProperties->pageLimit))
            {
                $this->limit = $datagridProperties->pageLimit;
            }
        }
    }

    public static function setDatagridProperties($param = [])
    {
        $datagridProperties = new stdClass();
        $datagridProperties->hideColumns = [];
        if(!empty($param['columns']))
        {
            foreach($param['columns'] as $column)
            {
                if($column['visible'] == 'false')
                {
                    $datagridProperties->hideColumns[$column['columnId']] = $column['columnId'];
                }
            }
        }

        $datagridProperties->pageLimit = 10;
        if(!empty($param['page_limit']))
        {
            $datagridProperties->pageLimit = (int) $param['page_limit'];
        }

        TTransaction::open('permission');

        $preferenceKey = TSession::getValue('userid').'_user_datagrid_properties';
        $preference = SystemPreference::find($preferenceKey);
        
        if(!$preference)
        {
            $preference = new SystemPreference;
            $preference->id = $preferenceKey;
            $preference->preference = json_encode([]);
        }
        
        $userDatagridProperties = json_decode($preference->preference, true) ?? [];
        
        $userDatagridProperties[__CLASS__] = $datagridProperties;
        $preference->preference = json_encode($userDatagridProperties);
        $preference->store();

        TTransaction::close();

        TSession::setValue(__CLASS__.'_datagrid_properties', $datagridProperties);

        $params = [];

        if(!empty($param['target_container']))
        {
            $params['target_container'] = $param['target_container'];
        }

        TApplication::showPage(self::class, 'onReload', $params);
    }
}