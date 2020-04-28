<?php
namespace App\Asset;

use Seriti\Tools\Tree;
//use Seriti\Tools\Crypt;
//use Seriti\Tools\Form;
//use Seriti\Tools\Secure;
//use Seriti\Tools\Audit;

class Account extends Tree
{
     
    public function setup($param = []) 
    {
        parent::setup($param); 

        $this->addTreeCol(array('id'=>'currency_id','type'=>'STRING','title'=>'Account base currency'));

        $this->addSelect('currency_id','SELECT currency_id, name FROM '.TABLE_PREFIX.'currency ORDER BY name');
    }
}

?>