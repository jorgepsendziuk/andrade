<?php

class Estado extends TRecord
{
    const TABLENAME  = 'estado';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('estado');
            
    }

    /**
     * Method getMunicipios
     */
    public function getMunicipios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_estado', '=', $this->id));
        return Municipio::getObjects( $criteria );
    }
    /**
     * Method getClientes
     */
    public function getClientesByFkEstados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('estado', '=', $this->id));
        return Cliente::getObjects( $criteria );
    }
    /**
     * Method getCondutorAutorizados
     */
    public function getCondutorAutorizadosByFkEstados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('estado', '=', $this->id));
        return CondutorAutorizado::getObjects( $criteria );
    }
    /**
     * Method getCondutorAutorizados
     */
    public function getCondutorAutorizadosByFkRgEstados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('rg_estado', '=', $this->id));
        return CondutorAutorizado::getObjects( $criteria );
    }
    /**
     * Method getClientes
     */
    public function getClientesByFkRgEstados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('rg_estado', '=', $this->id));
        return Cliente::getObjects( $criteria );
    }
    /**
     * Method getRepresentanteLegals
     */
    public function getRepresentanteLegals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('rg_estado', '=', $this->id));
        return RepresentanteLegal::getObjects( $criteria );
    }

    public function set_municipio_estado_to_string($municipio_estado_to_string)
    {
        if(is_array($municipio_estado_to_string))
        {
            $values = Estado::where('id', 'in', $municipio_estado_to_string)->getIndexedArray('id', 'id');
            $this->municipio_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->municipio_estado_to_string = $municipio_estado_to_string;
        }

        $this->vdata['municipio_estado_to_string'] = $this->municipio_estado_to_string;
    }

    public function get_municipio_estado_to_string()
    {
        if(!empty($this->municipio_estado_to_string))
        {
            return $this->municipio_estado_to_string;
        }
    
        $values = Municipio::where('id_estado', '=', $this->id)->getIndexedArray('id_estado','{estado->id}');
        return implode(', ', $values);
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
    
        $values = Cliente::where('rg_estado', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
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
    
        $values = Cliente::where('rg_estado', '=', $this->id)->getIndexedArray('municipio','{fk_municipio->id}');
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
    
        $values = Cliente::where('rg_estado', '=', $this->id)->getIndexedArray('estado','{fk_estado->id}');
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
    
        $values = Cliente::where('rg_estado', '=', $this->id)->getIndexedArray('id_representante','{representante->id}');
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
    
        $values = CondutorAutorizado::where('rg_estado', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
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
    
        $values = CondutorAutorizado::where('rg_estado', '=', $this->id)->getIndexedArray('municipio','{fk_municipio->id}');
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
    
        $values = CondutorAutorizado::where('rg_estado', '=', $this->id)->getIndexedArray('estado','{fk_estado->id}');
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
    
        $values = CondutorAutorizado::where('rg_estado', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
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
    
        $values = CondutorAutorizado::where('rg_estado', '=', $this->id)->getIndexedArray('condutor','{fk_condutor->id}');
        return implode(', ', $values);
    }

    public function set_representante_legal_fk_rg_estado_to_string($representante_legal_fk_rg_estado_to_string)
    {
        if(is_array($representante_legal_fk_rg_estado_to_string))
        {
            $values = Estado::where('id', 'in', $representante_legal_fk_rg_estado_to_string)->getIndexedArray('id', 'id');
            $this->representante_legal_fk_rg_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->representante_legal_fk_rg_estado_to_string = $representante_legal_fk_rg_estado_to_string;
        }

        $this->vdata['representante_legal_fk_rg_estado_to_string'] = $this->representante_legal_fk_rg_estado_to_string;
    }

    public function get_representante_legal_fk_rg_estado_to_string()
    {
        if(!empty($this->representante_legal_fk_rg_estado_to_string))
        {
            return $this->representante_legal_fk_rg_estado_to_string;
        }
    
        $values = RepresentanteLegal::where('rg_estado', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
        return implode(', ', $values);
    }

    
}

