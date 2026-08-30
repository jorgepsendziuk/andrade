<?php

class Municipio extends TRecord
{
    const TABLENAME  = 'municipio';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Estado $estado;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('municipio');
        parent::addAttribute('id_estado');
            
    }

    /**
     * Method set_estado
     * Sample of usage: $var->estado = $object;
     * @param $object Instance of Estado
     */
    public function set_estado(Estado $object)
    {
        $this->estado = $object;
        $this->id_estado = $object->id;
    }

    /**
     * Method get_estado
     * Sample of usage: $var->estado->attribute;
     * @returns Estado instance
     */
    public function get_estado()
    {
    
        // loads the associated object
        if (empty($this->estado))
            $this->estado = new Estado($this->id_estado);
    
        // returns the associated object
        return $this->estado;
    }

    /**
     * Method getClientes
     */
    public function getClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('municipio', '=', $this->id));
        return Cliente::getObjects( $criteria );
    }
    /**
     * Method getCondutorAutorizados
     */
    public function getCondutorAutorizados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('municipio', '=', $this->id));
        return CondutorAutorizado::getObjects( $criteria );
    }

    public function set_cliente_fk_rg_estado_to_string($cliente_fk_rg_estado_to_string)
    {
        if(is_array($cliente_fk_rg_estado_to_string))
        {
            $values = Estado::where('id', 'in', $cliente_fk_rg_estado_to_string)->getIndexedArray('id', 'id');
            $this->cliente_fk_rg_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->cliente_fk_rg_estado_to_string = $cliente_fk_rg_estado_to_string;
        }

        $this->vdata['cliente_fk_rg_estado_to_string'] = $this->cliente_fk_rg_estado_to_string;
    }

    public function get_cliente_fk_rg_estado_to_string()
    {
        if(!empty($this->cliente_fk_rg_estado_to_string))
        {
            return $this->cliente_fk_rg_estado_to_string;
        }
    
        $values = Cliente::where('municipio', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
        return implode(', ', $values);
    }

    public function set_cliente_fk_municipio_to_string($cliente_fk_municipio_to_string)
    {
        if(is_array($cliente_fk_municipio_to_string))
        {
            $values = Municipio::where('id', 'in', $cliente_fk_municipio_to_string)->getIndexedArray('id', 'id');
            $this->cliente_fk_municipio_to_string = implode(', ', $values);
        }
        else
        {
            $this->cliente_fk_municipio_to_string = $cliente_fk_municipio_to_string;
        }

        $this->vdata['cliente_fk_municipio_to_string'] = $this->cliente_fk_municipio_to_string;
    }

    public function get_cliente_fk_municipio_to_string()
    {
        if(!empty($this->cliente_fk_municipio_to_string))
        {
            return $this->cliente_fk_municipio_to_string;
        }
    
        $values = Cliente::where('municipio', '=', $this->id)->getIndexedArray('municipio','{fk_municipio->id}');
        return implode(', ', $values);
    }

    public function set_cliente_fk_estado_to_string($cliente_fk_estado_to_string)
    {
        if(is_array($cliente_fk_estado_to_string))
        {
            $values = Estado::where('id', 'in', $cliente_fk_estado_to_string)->getIndexedArray('id', 'id');
            $this->cliente_fk_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->cliente_fk_estado_to_string = $cliente_fk_estado_to_string;
        }

        $this->vdata['cliente_fk_estado_to_string'] = $this->cliente_fk_estado_to_string;
    }

    public function get_cliente_fk_estado_to_string()
    {
        if(!empty($this->cliente_fk_estado_to_string))
        {
            return $this->cliente_fk_estado_to_string;
        }
    
        $values = Cliente::where('municipio', '=', $this->id)->getIndexedArray('estado','{fk_estado->id}');
        return implode(', ', $values);
    }

    public function set_cliente_representante_to_string($cliente_representante_to_string)
    {
        if(is_array($cliente_representante_to_string))
        {
            $values = RepresentanteLegal::where('id', 'in', $cliente_representante_to_string)->getIndexedArray('id', 'id');
            $this->cliente_representante_to_string = implode(', ', $values);
        }
        else
        {
            $this->cliente_representante_to_string = $cliente_representante_to_string;
        }

        $this->vdata['cliente_representante_to_string'] = $this->cliente_representante_to_string;
    }

    public function get_cliente_representante_to_string()
    {
        if(!empty($this->cliente_representante_to_string))
        {
            return $this->cliente_representante_to_string;
        }
    
        $values = Cliente::where('municipio', '=', $this->id)->getIndexedArray('id_representante','{representante->id}');
        return implode(', ', $values);
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
    
        $values = CondutorAutorizado::where('municipio', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
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
    
        $values = CondutorAutorizado::where('municipio', '=', $this->id)->getIndexedArray('municipio','{fk_municipio->id}');
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
    
        $values = CondutorAutorizado::where('municipio', '=', $this->id)->getIndexedArray('estado','{fk_estado->id}');
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
    
        $values = CondutorAutorizado::where('municipio', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
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
    
        $values = CondutorAutorizado::where('municipio', '=', $this->id)->getIndexedArray('condutor','{fk_condutor->id}');
        return implode(', ', $values);
    }

    
}

