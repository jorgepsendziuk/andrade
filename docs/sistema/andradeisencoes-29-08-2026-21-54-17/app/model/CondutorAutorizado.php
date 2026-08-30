<?php

class CondutorAutorizado extends TRecord
{
    const TABLENAME  = 'condutor_autorizado';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Estado $fk_estado;
    private Municipio $fk_municipio;
    private Estado $fk_rg_estado;
    private Cliente $cliente;
    private Condutor $fk_condutor;

    

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
        parent::addAttribute('id_cliente');
        parent::addAttribute('condutor');
            
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
     * Method set_condutor
     * Sample of usage: $var->condutor = $object;
     * @param $object Instance of Condutor
     */
    public function set_fk_condutor(Condutor $object)
    {
        $this->fk_condutor = $object;
        $this->condutor = $object->id;
    }

    /**
     * Method get_fk_condutor
     * Sample of usage: $var->fk_condutor->attribute;
     * @returns Condutor instance
     */
    public function get_fk_condutor()
    {
    
        // loads the associated object
        if (empty($this->fk_condutor))
            $this->fk_condutor = new Condutor($this->condutor);
    
        // returns the associated object
        return $this->fk_condutor;
    }

    
}

