<?php

class Arquivo extends TRecord
{
    const TABLENAME  = 'arquivo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Cliente $cliente;
    private ArquivoTipo $tp_arquivo;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('id_cliente');
        parent::addAttribute('id_tp_arquivo');
        parent::addAttribute('arquivo');
            
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
     * Method set_arquivo_tipo
     * Sample of usage: $var->arquivo_tipo = $object;
     * @param $object Instance of ArquivoTipo
     */
    public function set_tp_arquivo(ArquivoTipo $object)
    {
        $this->tp_arquivo = $object;
        $this->id_tp_arquivo = $object->id;
    }

    /**
     * Method get_tp_arquivo
     * Sample of usage: $var->tp_arquivo->attribute;
     * @returns ArquivoTipo instance
     */
    public function get_tp_arquivo()
    {
    
        // loads the associated object
        if (empty($this->tp_arquivo))
            $this->tp_arquivo = new ArquivoTipo($this->id_tp_arquivo);
    
        // returns the associated object
        return $this->tp_arquivo;
    }

    
}

