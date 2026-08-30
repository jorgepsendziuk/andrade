<?php

class PagamentoTipo extends TRecord
{
    const TABLENAME  = 'pagamento_tipo';
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
     * Method getContratos
     */
    public function getContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pagamento_tipo', '=', $this->id));
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
    
        $values = Contrato::where('pagamento_tipo', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
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
    
        $values = Contrato::where('pagamento_tipo', '=', $this->id)->getIndexedArray('id_carro','{carro->id}');
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
    
        $values = Contrato::where('pagamento_tipo', '=', $this->id)->getIndexedArray('pagamento_tipo','{fk_pagamento_tipo->id}');
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
    
        $values = Contrato::where('pagamento_tipo', '=', $this->id)->getIndexedArray('pagamento_status','{fk_pagamento_status->id}');
        return implode(', ', $values);
    }

    
}

