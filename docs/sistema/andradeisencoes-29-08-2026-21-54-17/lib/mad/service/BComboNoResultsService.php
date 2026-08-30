<?php

class BComboNoResultsService
{
    public static function getQuickFieldValue($param)
    {
        if(!empty($param['_field_data_json']))
        {
            $data = json_decode($param['_field_data_json']);
            return $data->quick_register_value;
        }

        return '';
    }

    public static function getProperties($param)
    {
        if(!empty($param['_field_data_json']))
        {
            $data = json_decode($param['_field_data_json']);

            if(!empty($data->noresultsbtnprops))
            {
                $props = unserialize(base64_decode(Crypt::decryptString($data->noresultsbtnprops)));

                if(!empty($data->id) && !empty($props->field_id) && $data->id != $props->field_id)
                {
                    $props->field_id = $data->id;
                }
     
                return $props;
            }
        }

        if(!empty($param['form_noresultsbtnprops']))
        {
            $data = json_decode($param['form_noresultsbtnprops']);

            $props = unserialize(base64_decode(Crypt::decryptString($data->noresultsbtnprops)));
            
            if(!empty($data->id) && !empty($props->field_id) && $data->id != $props->field_id)
            {
                $props->field_id = $data->id;
            }
            
            return $props;
        }

        if(!empty($param['noresultsbtnprops']))
        {
            $data = json_decode($param['noresultsbtnprops']);

            $props = unserialize(base64_decode(Crypt::decryptString($data->noresultsbtnprops)));
            
            if(!empty($data->id) && !empty($props->field_id) && $data->id != $props->field_id)
            {
                $props->field_id = $data->id;
            }
            
            return $props;
        }

        return false;
    }

    public static function getPropertiesJson($param)
    {
        if(!empty($param['noresultsbtnprops']))
        {
            return $param['noresultsbtnprops'];
        }

        if(!empty($param['_field_data_json']))
        {
            return $param['_field_data_json'];
        }

        if(!empty($param['form_noresultsbtnprops']))
        {
            return $param['form_noresultsbtnprops'];
        }
    
        return false;
    }

    /**
     * Handles the refresh of component in screen with new record data
     * 
     * @param array $param form data containing component information
     * @param object $object Newly created record
     * @return void
     */
    public static function handleRefreshComponent($param, $object)
    {
        $props = self::getProperties($param);

        if($props && in_array($props->component, ['TDBCombo', 'TCombo']))
        {
            TCombo::addOption($props->field_form, $props->field_id ?? $props->field_name, $object->{$props->key}, $object->render($props->column));
            TForm::sendData($props->field_form, (object) [$props->field_id ?? $props->field_name => $object->{$props->key} ]);
        }
        elseif($props && in_array($props->component, ['TDBUniqueSearch']))
        {
            TForm::sendData($props->field_form, (object) [$props->field_id ?? $props->field_name => $object->{$props->key} ]);
        }
    }
}