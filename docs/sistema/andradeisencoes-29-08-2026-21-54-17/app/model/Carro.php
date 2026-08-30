<?php

class Carro extends TRecord
{
    const TABLENAME  = 'carro';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private CarroModelo $modelo;
    private IsencaoIpva $fk_tipo_isencao;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_modelo');
        parent::addAttribute('placa');
        parent::addAttribute('renavam');
        parent::addAttribute('chassi');
        parent::addAttribute('tipo_isencao');
            
    }

    /**
     * Method set_carro_modelo
     * Sample of usage: $var->carro_modelo = $object;
     * @param $object Instance of CarroModelo
     */
    public function set_modelo(CarroModelo $object)
    {
        $this->modelo = $object;
        $this->id_modelo = $object->id;
    }

    /**
     * Method get_modelo
     * Sample of usage: $var->modelo->attribute;
     * @returns CarroModelo instance
     */
    public function get_modelo()
    {
    
        // loads the associated object
        if (empty($this->modelo))
            $this->modelo = new CarroModelo($this->id_modelo);
    
        // returns the associated object
        return $this->modelo;
    }
    /**
     * Method set_isencao_ipva
     * Sample of usage: $var->isencao_ipva = $object;
     * @param $object Instance of IsencaoIpva
     */
    public function set_fk_tipo_isencao(IsencaoIpva $object)
    {
        $this->fk_tipo_isencao = $object;
        $this->tipo_isencao = $object->id;
    }

    /**
     * Method get_fk_tipo_isencao
     * Sample of usage: $var->fk_tipo_isencao->attribute;
     * @returns IsencaoIpva instance
     */
    public function get_fk_tipo_isencao()
    {
    
        // loads the associated object
        if (empty($this->fk_tipo_isencao))
            $this->fk_tipo_isencao = new IsencaoIpva($this->tipo_isencao);
    
        // returns the associated object
        return $this->fk_tipo_isencao;
    }

    /**
     * Method getContratos
     */
    public function getContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_carro', '=', $this->id));
        return Contrato::getObjects( $criteria );
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
    
        $values = Contrato::where('id_carro', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
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
    
        $values = Contrato::where('id_carro', '=', $this->id)->getIndexedArray('id_carro','{carro->id}');
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
    
        $values = Contrato::where('id_carro', '=', $this->id)->getIndexedArray('pagamento_tipo','{fk_pagamento_tipo->id}');
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
    
        $values = Contrato::where('id_carro', '=', $this->id)->getIndexedArray('pagamento_status','{fk_pagamento_status->id}');
        return implode(', ', $values);
    }

    
}

