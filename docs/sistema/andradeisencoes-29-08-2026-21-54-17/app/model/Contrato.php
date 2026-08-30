<?php

class Contrato extends TRecord
{
    const TABLENAME  = 'contrato';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Cliente $cliente;
    private Carro $carro;
    private PagamentoTipo $fk_pagamento_tipo;
    private PagamentoStatus $fk_pagamento_status;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_cliente');
        parent::addAttribute('id_carro');
        parent::addAttribute('data_contrato');
        parent::addAttribute('pagamento_tipo');
        parent::addAttribute('pagamento_status');
            
    }

    /**
     * Method set_cliente
     * Sample of usage: $var->cliente = $object;
     * @param $object Instance of Cliente
     */
    public function set_cliente(Cliente $object)
    {
        $this->cliente = $object;
        $this->id_cliente = $object->id;
    }

    /**
     * Method get_cliente
     * Sample of usage: $var->cliente->attribute;
     * @returns Cliente instance
     */
    public function get_cliente()
    {
    
        // loads the associated object
        if (empty($this->cliente))
            $this->cliente = new Cliente($this->id_cliente);
    
        // returns the associated object
        return $this->cliente;
    }
    /**
     * Method set_carro
     * Sample of usage: $var->carro = $object;
     * @param $object Instance of Carro
     */
    public function set_carro(Carro $object)
    {
        $this->carro = $object;
        $this->id_carro = $object->id;
    }

    /**
     * Method get_carro
     * Sample of usage: $var->carro->attribute;
     * @returns Carro instance
     */
    public function get_carro()
    {
    
        // loads the associated object
        if (empty($this->carro))
            $this->carro = new Carro($this->id_carro);
    
        // returns the associated object
        return $this->carro;
    }
    /**
     * Method set_pagamento_tipo
     * Sample of usage: $var->pagamento_tipo = $object;
     * @param $object Instance of PagamentoTipo
     */
    public function set_fk_pagamento_tipo(PagamentoTipo $object)
    {
        $this->fk_pagamento_tipo = $object;
        $this->pagamento_tipo = $object->id;
    }

    /**
     * Method get_fk_pagamento_tipo
     * Sample of usage: $var->fk_pagamento_tipo->attribute;
     * @returns PagamentoTipo instance
     */
    public function get_fk_pagamento_tipo()
    {
    
        // loads the associated object
        if (empty($this->fk_pagamento_tipo))
            $this->fk_pagamento_tipo = new PagamentoTipo($this->pagamento_tipo);
    
        // returns the associated object
        return $this->fk_pagamento_tipo;
    }
    /**
     * Method set_pagamento_status
     * Sample of usage: $var->pagamento_status = $object;
     * @param $object Instance of PagamentoStatus
     */
    public function set_fk_pagamento_status(PagamentoStatus $object)
    {
        $this->fk_pagamento_status = $object;
        $this->pagamento_status = $object->id;
    }

    /**
     * Method get_fk_pagamento_status
     * Sample of usage: $var->fk_pagamento_status->attribute;
     * @returns PagamentoStatus instance
     */
    public function get_fk_pagamento_status()
    {
    
        // loads the associated object
        if (empty($this->fk_pagamento_status))
            $this->fk_pagamento_status = new PagamentoStatus($this->pagamento_status);
    
        // returns the associated object
        return $this->fk_pagamento_status;
    }

    /**
     * Method getPagamentoArquivoss
     */
    public function getPagamentoArquivoss()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_contrato', '=', $this->id));
        return PagamentoArquivos::getObjects( $criteria );
    }

    public function set_pagamento_arquivos_contrato_to_string($pagamento_arquivos_contrato_to_string)
    {
        if(is_array($pagamento_arquivos_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $pagamento_arquivos_contrato_to_string)->getIndexedArray('id', 'id');
            $this->pagamento_arquivos_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->pagamento_arquivos_contrato_to_string = $pagamento_arquivos_contrato_to_string;
        }

        $this->vdata['pagamento_arquivos_contrato_to_string'] = $this->pagamento_arquivos_contrato_to_string;
    }

    public function get_pagamento_arquivos_contrato_to_string()
    {
        if(!empty($this->pagamento_arquivos_contrato_to_string))
        {
            return $this->pagamento_arquivos_contrato_to_string;
        }
    
        $values = PagamentoArquivos::where('id_contrato', '=', $this->id)->getIndexedArray('id_contrato','{contrato->id}');
        return implode(', ', $values);
    }

    
}

