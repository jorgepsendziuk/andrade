<?php

class CarroMarca extends TRecord
{
    const TABLENAME  = 'carro_marca';
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
     * Method getCarroModelos
     */
    public function getCarroModelos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('marca', '=', $this->id));
        return CarroModelo::getObjects( $criteria );
    }

    public function set_carro_modelo_fk_marca_to_string($carro_modelo_fk_marca_to_string)
    {
        if(is_array($carro_modelo_fk_marca_to_string))
        {
            $values = CarroMarca::where('id', 'in', $carro_modelo_fk_marca_to_string)->getIndexedArray('id', 'id');
            $this->carro_modelo_fk_marca_to_string = implode(', ', $values);
        }
        else
        {
            $this->carro_modelo_fk_marca_to_string = $carro_modelo_fk_marca_to_string;
        }

        $this->vdata['carro_modelo_fk_marca_to_string'] = $this->carro_modelo_fk_marca_to_string;
    }

    public function get_carro_modelo_fk_marca_to_string()
    {
        if(!empty($this->carro_modelo_fk_marca_to_string))
        {
            return $this->carro_modelo_fk_marca_to_string;
        }
    
        $values = CarroModelo::where('marca', '=', $this->id)->getIndexedArray('marca','{fk_marca->id}');
        return implode(', ', $values);
    }

    
}

