<?php

class ArquivoTipo extends TRecord
{
    const TABLENAME  = 'arquivo_tipo';
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
     * Method getArquivos
     */
    public function getArquivos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_tp_arquivo', '=', $this->id));
        return Arquivo::getObjects( $criteria );
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
    
        $values = Arquivo::where('id_tp_arquivo', '=', $this->id)->getIndexedArray('id_cliente','{cliente->id}');
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
    
        $values = Arquivo::where('id_tp_arquivo', '=', $this->id)->getIndexedArray('id_tp_arquivo','{tp_arquivo->id}');
        return implode(', ', $values);
    }

    
}

