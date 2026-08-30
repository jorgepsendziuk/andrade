<?php

class Cliente extends TRecord
{
    const TABLENAME  = 'cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Municipio $fk_municipio;
    private Estado $fk_estado;
    private Estado $fk_rg_estado;
    private RepresentanteLegal $representante;

    

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
        parent::addAttribute('rg_data_emissao');
        parent::addAttribute('email');
        parent::addAttribute('endereco');
        parent::addAttribute('numero');
        parent::addAttribute('complemento');
        parent::addAttribute('bairro');
        parent::addAttribute('cep');
        parent::addAttribute('municipio');
        parent::addAttribute('estado');
        parent::addAttribute('id_representante');
        parent::addAttribute('telefone');
            
    }

    /**
     * Method set_municipio
     * Sample of usage: $var->municipio = $object;
     * @param $object Instance of Municipio
     */
    public function set_fk_municipio(Municipio $object)
    {
        $this->fk_municipio = $object;
        $this->municipio = $object->id;
    }

    /**
     * Method get_fk_municipio
     * Sample of usage: $var->fk_municipio->attribute;
     * @returns Municipio instance
     */
    public function get_fk_municipio()
    {
    
        // loads the associated object
        if (empty($this->fk_municipio))
            $this->fk_municipio = new Municipio($this->municipio);
    
        // returns the associated object
        return $this->fk_municipio;
    }
    /**
     * Method set_estado
     * Sample of usage: $var->estado = $object;
     * @param $object Instance of Estado
     */
    public function set_fk_estado(Estado $object)
    {
        $this->fk_estado = $object;
        $this->estado = $object->id;
    }

    /**
     * Method get_fk_estado
     * Sample of usage: $var->fk_estado->attribute;
     * @returns Estado instance
     */
    public function get_fk_estado()
    {
    
        // loads the associated object
        if (empty($this->fk_estado))
            $this->fk_estado = new Estado($this->estado);
    
        // returns the associated object
        return $this->fk_estado;
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
     * Method set_representante_legal
     * Sample of usage: $var->representante_legal = $object;
     * @param $object Instance of RepresentanteLegal
     */
    public function set_representante(RepresentanteLegal $object)
    {
        $this->representante = $object;
        $this->id_representante = $object->id;
    }

    /**
     * Method get_representante
     * Sample of usage: $var->representante->attribute;
     * @returns RepresentanteLegal instance
     */
    public function get_representante()
    {
    
        // loads the associated object
        if (empty($this->representante))
            $this->representante = new RepresentanteLegal($this->id_representante);
    
        // returns the associated object
        return $this->representante;
    }

    /**
     * Method getArquivos
     */
    public function getArquivos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_cliente', '=', $this->id));
        return Arquivo::getObjects( $criteria );
    }
    /**
     * Method getContratos
     */
    public function getContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_cliente', '=', $this->id));
        return Contrato::getObjects( $criteria );
    }
    /**
     * Method getCondutorAutorizados
     */
    public function getCondutorAutorizados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_cliente', '=', $this->id));
        return CondutorAutorizado::getObjects( $criteria );
    }

    public function set_arquivo_cliente_to_string($arquivo_cliente_to_string)
    {
        if(is_array($arquivo_cliente_to_string))
        {
            $values = Cliente::where('id', 'in', $arquivo_cliente_to_string)->getIndexedArray('id', 'id');
            $this->arquivo_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->arquivo_cliente_to_string = $arquivo_cliente_to_string;
        }

        $this->vdata['arquivo_cliente_to_string'] = $this->arquivo_cliente_to_string;
    }

    public function get_arquivo_cliente_to_string()
    {
        if(!empty($this->arquivo_cliente_to_string))
        {
            return $this->arquivo_cliente_to_string;
        }
    
        $values = Arquivo::where('id_cliente', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
        return implode(', ', $values);
    }

    public function set_arquivo_tp_arquivo_to_string($arquivo_tp_arquivo_to_string)
    {
        if(is_array($arquivo_tp_arquivo_to_string))
        {
            $values = ArquivoTipo::where('id', 'in', $arquivo_tp_arquivo_to_string)->getIndexedArray('id', 'id');
            $this->arquivo_tp_arquivo_to_string = implode(', ', $values);
        }
        else
        {
            $this->arquivo_tp_arquivo_to_string = $arquivo_tp_arquivo_to_string;
        }

        $this->vdata['arquivo_tp_arquivo_to_string'] = $this->arquivo_tp_arquivo_to_string;
    }

    public function get_arquivo_tp_arquivo_to_string()
    {
        if(!empty($this->arquivo_tp_arquivo_to_string))
        {
            return $this->arquivo_tp_arquivo_to_string;
        }
    
        $values = Arquivo::where('id_cliente', '=', $this->id)->getIndexedArray('id_tp_arquivo','{tp_arquivo->id}');
        return implode(', ', $values);
    }

    public function set_contrato_cliente_to_string($contrato_cliente_to_string)
    {
        if(is_array($contrato_cliente_to_string))
        {
            $values = Cliente::where('id', 'in', $contrato_cliente_to_string)->getIndexedArray('id', 'id');
            $this->contrato_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_cliente_to_string = $contrato_cliente_to_string;
        }

        $this->vdata['contrato_cliente_to_string'] = $this->contrato_cliente_to_string;
    }

    public function get_contrato_cliente_to_string()
    {
        if(!empty($this->contrato_cliente_to_string))
        {
            return $this->contrato_cliente_to_string;
        }
    
        $values = Contrato::where('id_cliente', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
        return implode(', ', $values);
    }

    public function set_contrato_carro_to_string($contrato_carro_to_string)
    {
        if(is_array($contrato_carro_to_string))
        {
            $values = Carro::where('id', 'in', $contrato_carro_to_string)->getIndexedArray('id', 'id');
            $this->contrato_carro_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_carro_to_string = $contrato_carro_to_string;
        }

        $this->vdata['contrato_carro_to_string'] = $this->contrato_carro_to_string;
    }

    public function get_contrato_carro_to_string()
    {
        if(!empty($this->contrato_carro_to_string))
        {
            return $this->contrato_carro_to_string;
        }
    
        $values = Contrato::where('id_cliente', '=', $this->id)->getIndexedArray('id_carro','{carro->id}');
        return implode(', ', $values);
    }

    public function set_contrato_fk_pagamento_tipo_to_string($contrato_fk_pagamento_tipo_to_string)
    {
        if(is_array($contrato_fk_pagamento_tipo_to_string))
        {
            $values = PagamentoTipo::where('id', 'in', $contrato_fk_pagamento_tipo_to_string)->getIndexedArray('id', 'id');
            $this->contrato_fk_pagamento_tipo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_fk_pagamento_tipo_to_string = $contrato_fk_pagamento_tipo_to_string;
        }

        $this->vdata['contrato_fk_pagamento_tipo_to_string'] = $this->contrato_fk_pagamento_tipo_to_string;
    }

    public function get_contrato_fk_pagamento_tipo_to_string()
    {
        if(!empty($this->contrato_fk_pagamento_tipo_to_string))
        {
            return $this->contrato_fk_pagamento_tipo_to_string;
        }
    
        $values = Contrato::where('id_cliente', '=', $this->id)->getIndexedArray('pagamento_tipo','{fk_pagamento_tipo->id}');
        return implode(', ', $values);
    }

    public function set_contrato_fk_pagamento_status_to_string($contrato_fk_pagamento_status_to_string)
    {
        if(is_array($contrato_fk_pagamento_status_to_string))
        {
            $values = PagamentoStatus::where('id', 'in', $contrato_fk_pagamento_status_to_string)->getIndexedArray('id', 'id');
            $this->contrato_fk_pagamento_status_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_fk_pagamento_status_to_string = $contrato_fk_pagamento_status_to_string;
        }

        $this->vdata['contrato_fk_pagamento_status_to_string'] = $this->contrato_fk_pagamento_status_to_string;
    }

    public function get_contrato_fk_pagamento_status_to_string()
    {
        if(!empty($this->contrato_fk_pagamento_status_to_string))
        {
            return $this->contrato_fk_pagamento_status_to_string;
        }
    
        $values = Contrato::where('id_cliente', '=', $this->id)->getIndexedArray('pagamento_status','{fk_pagamento_status->id}');
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
    
        $values = CondutorAutorizado::where('id_cliente', '=', $this->id)->getIndexedArray('rg_estado','{fk_rg_estado->id}');
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
    
        $values = CondutorAutorizado::where('id_cliente', '=', $this->id)->getIndexedArray('municipio','{fk_municipio->id}');
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
    
        $values = CondutorAutorizado::where('id_cliente', '=', $this->id)->getIndexedArray('estado','{fk_estado->id}');
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
    
        $values = CondutorAutorizado::where('id_cliente', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
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
    
        $values = CondutorAutorizado::where('id_cliente', '=', $this->id)->getIndexedArray('condutor','{fk_condutor->id}');
        return implode(', ', $values);
    }

    
}

