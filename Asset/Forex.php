<?php 
namespace App\Asset;

use Seriti\Tools\Table;

class Forex extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Rate','col_label'=>'rate'];
        parent::setup($param);        
       
        $this->addTableCol(array('id'=>'forex_id','type'=>'INTEGER','title'=>'Forex ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'currency_id_transact','type'=>'STRING','title'=>'Currency ID Asset'));
        $this->addTableCol(array('id'=>'currency_id_portfolio','type'=>'STRING','title'=>'Currency ID Portfolio'));
        $this->addTableCol(array('id'=>'year','type'=>'INTEGER','title'=>'Year','new'=>date('Y')));
        $this->addTableCol(array('id'=>'month','type'=>'INTEGER','title'=>'Month','new'=>date('m')));
        $this->addTableCol(array('id'=>'rate','type'=>'DECIMAL','title'=>'Convert to Portfolio rate'));

        $this->addSortOrder('T.`Year` DESC, T.`month` DESC ','Most recent first','DEFAULT');

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));

        $this->addSearch(array('currency_id_transact','year','month','rate'),array('rows'=>1));

        $this->addSelect('currency_id_transact','SELECT `currency_id`, `name` FROM `'.TABLE_PREFIX.'currency` ORDER BY `currency_id`');
        $this->addSelect('currency_id_portfolio','SELECT `currency_id`, `name` FROM `'.TABLE_PREFIX.'currency` ORDER BY `currency_id`');
    }    
}