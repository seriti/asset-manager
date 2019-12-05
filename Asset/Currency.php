<?php 
namespace App\Asset;

use Seriti\Tools\Table;

use App\Asset\TABLE_PREFIX;

class Currency extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Currency','row_name_plural'=>'Currencies','col_label'=>'name'];
        parent::setup($param);        

        $this->addForeignKey(array('table'=>TABLE_PREFIX.'asset','col_id'=>'currency_id','message'=>'Asset exists for this currency'));
        $this->addForeignKey(array('table'=>TABLE_PREFIX.'portfolio','col_id'=>'currency_id','message'=>'Portfolio exists for this currency'));  

        $this->addTableCol(array('id'=>'currency_id','type'=>'STRING','title'=>'Currency ID','max'=>4,'key'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Asset name'));
        $this->addTableCol(array('id'=>'symbol','type'=>'STRING','title'=>'Symbol'));
        $this->addTableCol(array('id'=>'risk_free_rate','type'=>'DECIMAL','title'=>'Risk free rate(%)','new'=>'5'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status','new'=>'OK'));

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));
        //$this->addAction(array('type'=>'popup','text'=>'Exchange rates','url'=>'currency_rate','mode'=>'view','width'=>600,'height'=>600)); 

        $this->addSearch(array('currency_id','name','symbol','status'),array('rows'=>2));
            
        $this->addSelect('status','(SELECT "OK") UNION (SELECT "HIDE")');
    }
} 