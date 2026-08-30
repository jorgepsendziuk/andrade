<?php

class IsencaoIpva extends TRecord
{
    const TABLENAME  = 'isencao_ipva';
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
     * Method getCarros
     */
    public function getCarros()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_isencao', '=', $this->id));
        return Carro::getObjects( $criteria );
    }

    public function set_carro_modelo_to_string($carro_modelo_to_string)
    {
        if(is_array($carro_modelo_to_string))
        {
            $values = CarroModelo::where('id', 'in', $carro_modelo_to_string)->getIndexedArray('id', 'id');
            $this->carro_modelo_to_string = implode(', ', $values);
        }
        else
        {
            $this->carro_modelo_to_string = $carro_modelo_to_string;
        }

        $this->vdata['carro_modelo_to_string'] = $this->carro_modelo_to_string;
    }

    public function get_carro_modelo_to_string()
    {
        if(!empty($this->carro_modelo_to_string))
        {
            return $this->carro_modelo_to_string;
        }
    
        $values = Carro::where('tipo_isencao', '=', $this->id)->getIndexedArray('id_modelo','{modelo->id}');
        return implode(', ', $values);
    }

    public function set_carro_fk_tipo_isencao_to_string($carro_fk_tipo_isencao_to_string)
    {
        if(is_array($carro_fk_tipo_isencao_to_string))
        {
            $values = IsencaoIpva::where('id', 'in', $carro_fk_tipo_isencao_to_string)->getIndexedArray('id', 'id');
            $this->carro_fk_tipo_isencao_to_string = implode(', ', $values);
        }
        else
        {
            $this->carro_fk_tipo_isencao_to_string = $carro_fk_tipo_isencao_to_string;
        }

        $this->vdata['carro_fk_tipo_isencao_to_string'] = $this->carro_fk_tipo_isencao_to_string;
    }

    public function get_carro_fk_tipo_isencao_to_string()
    {
        if(!empty($this->carro_fk_tipo_isencao_to_string))
        {
            return $this->carro_fk_tipo_isencao_to_string;
        }
    
        $values = Carro::where('tipo_isencao', '=', $this->id)->getIndexedArray('tipo_isencao','{fk_tipo_isencao->id}');
        return implode(', ', $values);
    }

    
}

