<?php

class RepresentanteLegal extends TRecord
{
    const TABLENAME  = 'representante_legal';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Estado $fk_rg_estado;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('cpf');
        parent::addAttribute('rg');
        parent::addAttribute('rg_estado');
        parent::addAttribute('rg_orgao_emissor');
        parent::addAttribute('telefone');
            
    }

    /**
     * Method set_estado
     * Sample of usage: $var->estado = $object;
     * @param $object Instance of Estado
     */
    public function set_fk_rg_estado(Estado $object)
    {
        $this->fk_rg_estado = $object;
        $this->rg_estado = $object->id;
    }

    /**
     * Method get_fk_rg_estado
     * Sample of usage: $var->fk_rg_estado->attribute;
     * @returns Estado instance
     */
    public function get_fk_rg_estado()
    {
    
        // loads the associated object
        if (empty($this->fk_rg_estado))
            $this->fk_rg_estado = new Estado($this->rg_estado);
    
        // returns the associated object
        return $this->fk_rg_estado;
    }

    /**
     * Method getClientes
     */
    public function getClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_representante', '=', $this->id));
        return Cliente::getObjects( $criteria );
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
    
        $values = Cliente::where('id_representante', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
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
    
        $values = Cliente::where('id_representante', '=', $this->id)->getIndexedArray('municipio','{fk_municipio->id}');
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
    
        $values = Cliente::where('id_representante', '=', $this->id)->getIndexedArray('estado','{fk_estado->id}');
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
    
        $values = Cliente::where('id_representante', '=', $this->id)->getIndexedArray('id_representante','{representante->id}');
        return implode(', ', $values);
    }

    
}

