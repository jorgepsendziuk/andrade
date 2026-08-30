<?php

class Condutor extends TRecord
{
    const TABLENAME  = 'condutor';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('descricao');
            
    }

    /**
     * Method getCondutorAutorizados
     */
    public function getCondutorAutorizados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('condutor', '=', $this->id));
        return CondutorAutorizado::getObjects( $criteria );
    }

    public function set_condutor_autorizado_fk_rg_estado_to_string($condutor_autorizado_fk_rg_estado_to_string)
    {
        if(is_array($condutor_autorizado_fk_rg_estado_to_string))
        {
            $values = Estado::where('id', 'in', $condutor_autorizado_fk_rg_estado_to_string)->getIndexedArray('id', 'id');
            $this->condutor_autorizado_fk_rg_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->condutor_autorizado_fk_rg_estado_to_string = $condutor_autorizado_fk_rg_estado_to_string;
        }

        $this->vdata['condutor_autorizado_fk_rg_estado_to_string'] = $this->condutor_autorizado_fk_rg_estado_to_string;
    }

    public function get_condutor_autorizado_fk_rg_estado_to_string()
    {
        if(!empty($this->condutor_autorizado_fk_rg_estado_to_string))
        {
            return $this->condutor_autorizado_fk_rg_estado_to_string;
        }
    
        $values = CondutorAutorizado::where('condutor', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
        return implode(', ', $values);
    }

    public function set_condutor_autorizado_fk_municipio_to_string($condutor_autorizado_fk_municipio_to_string)
    {
        if(is_array($condutor_autorizado_fk_municipio_to_string))
        {
            $values = Municipio::where('id', 'in', $condutor_autorizado_fk_municipio_to_string)->getIndexedArray('id', 'id');
            $this->condutor_autorizado_fk_municipio_to_string = implode(', ', $values);
        }
        else
        {
            $this->condutor_autorizado_fk_municipio_to_string = $condutor_autorizado_fk_municipio_to_string;
        }

        $this->vdata['condutor_autorizado_fk_municipio_to_string'] = $this->condutor_autorizado_fk_municipio_to_string;
    }

    public function get_condutor_autorizado_fk_municipio_to_string()
    {
        if(!empty($this->condutor_autorizado_fk_municipio_to_string))
        {
            return $this->condutor_autorizado_fk_municipio_to_string;
        }
    
        $values = CondutorAutorizado::where('condutor', '=', $this->id)->getIndexedArray('municipio','{fk_municipio->id}');
        return implode(', ', $values);
    }

    public function set_condutor_autorizado_fk_estado_to_string($condutor_autorizado_fk_estado_to_string)
    {
        if(is_array($condutor_autorizado_fk_estado_to_string))
        {
            $values = Estado::where('id', 'in', $condutor_autorizado_fk_estado_to_string)->getIndexedArray('id', 'id');
            $this->condutor_autorizado_fk_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->condutor_autorizado_fk_estado_to_string = $condutor_autorizado_fk_estado_to_string;
        }

        $this->vdata['condutor_autorizado_fk_estado_to_string'] = $this->condutor_autorizado_fk_estado_to_string;
    }

    public function get_condutor_autorizado_fk_estado_to_string()
    {
        if(!empty($this->condutor_autorizado_fk_estado_to_string))
        {
            return $this->condutor_autorizado_fk_estado_to_string;
        }
    
        $values = CondutorAutorizado::where('condutor', '=', $this->id)->getIndexedArray('estado','{fk_estado->id}');
        return implode(', ', $values);
    }

    public function set_condutor_autorizado_cliente_to_string($condutor_autorizado_cliente_to_string)
    {
        if(is_array($condutor_autorizado_cliente_to_string))
        {
            $values = Cliente::where('id', 'in', $condutor_autorizado_cliente_to_string)->getIndexedArray('id', 'id');
            $this->condutor_autorizado_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->condutor_autorizado_cliente_to_string = $condutor_autorizado_cliente_to_string;
        }

        $this->vdata['condutor_autorizado_cliente_to_string'] = $this->condutor_autorizado_cliente_to_string;
    }

    public function get_condutor_autorizado_cliente_to_string()
    {
        if(!empty($this->condutor_autorizado_cliente_to_string))
        {
            return $this->condutor_autorizado_cliente_to_string;
        }
    
        $values = CondutorAutorizado::where('condutor', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
        return implode(', ', $values);
    }

    public function set_condutor_autorizado_fk_condutor_to_string($condutor_autorizado_fk_condutor_to_string)
    {
        if(is_array($condutor_autorizado_fk_condutor_to_string))
        {
            $values = Condutor::where('id', 'in', $condutor_autorizado_fk_condutor_to_string)->getIndexedArray('id', 'id');
            $this->condutor_autorizado_fk_condutor_to_string = implode(', ', $values);
        }
        else
        {
            $this->condutor_autorizado_fk_condutor_to_string = $condutor_autorizado_fk_condutor_to_string;
        }

        $this->vdata['condutor_autorizado_fk_condutor_to_string'] = $this->condutor_autorizado_fk_condutor_to_string;
    }

    public function get_condutor_autorizado_fk_condutor_to_string()
    {
        if(!empty($this->condutor_autorizado_fk_condutor_to_string))
        {
            return $this->condutor_autorizado_fk_condutor_to_string;
        }
    
        $values = CondutorAutorizado::where('condutor', '=', $this->id)->getIndexedArray('condutor','{fk_condutor->id}');
        return implode(', ', $values);
    }

    
}

