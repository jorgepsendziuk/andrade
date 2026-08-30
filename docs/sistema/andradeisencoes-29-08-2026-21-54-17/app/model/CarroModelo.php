<?php

class CarroModelo extends TRecord
{
    const TABLENAME  = 'carro_modelo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private CarroMarca $fk_marca;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('modelo');
        parent::addAttribute('marca');
            
    }

    /**
     * Method set_carro_marca
     * Sample of usage: $var->carro_marca = $object;
     * @param $object Instance of CarroMarca
     */
    public function set_fk_marca(CarroMarca $object)
    {
        $this->fk_marca = $object;
        $this->marca = $object->id;
    }

    /**
     * Method get_fk_marca
     * Sample of usage: $var->fk_marca->attribute;
     * @returns CarroMarca instance
     */
    public function get_fk_marca()
    {
    
        // loads the associated object
        if (empty($this->fk_marca))
            $this->fk_marca = new CarroMarca($this->marca);
    
        // returns the associated object
        return $this->fk_marca;
    }

    /**
     * Method getCarros
     */
    public function getCarros()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('id_modelo', '=', $this->id));
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
    
        $values = Carro::where('id_modelo', '=', $this->id)->getIndexedArray('id_modelo','{modelo->id}');
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
    
        $values = Carro::where('id_modelo', '=', $this->id)->getIndexedArray('tipo_isencao','{fk_tipo_isencao->id}');
        return implode(', ', $values);
    }

    
}

