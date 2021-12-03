<?php 
namespace App\Asset;

use Seriti\Tools\Table;
use Seriti\Tools\CURRENCY_ID;

use App\Asset\TABLE_PREFIX;
use App\Asset\ACC_TYPE;

use App\Asset\Helpers;


class Portfolio extends Table 
{
    
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Portfolio','row_name_plural'=>'Portfolios','col_label'=>'name'];
        parent::setup($param);        

        $this->addForeignKey(array('table'=>TABLE_PREFIX.'asset','col_id'=>'portfolio_id','message'=>'Asset'));
        $this->addForeignKey(array('table'=>TABLE_PREFIX.'price','col_id'=>'portfolio_id','message'=>'Price'));
        //$this->addForeignKey(array('table'=>TABLE_PREFIX.'balance','col_id'=>'portfolio_id','message'=>'Balance'));
        $this->addForeignKey(array('table'=>TABLE_PREFIX.'transact','col_id'=>'portfolio_id','message'=>'Transaction'));

        $this->addTableCol(array('id'=>'portfolio_id','type'=>'INTEGER','title'=>'Portfolio ID','key'=>true,'key_auto'=>true,'list'=>false));
        if(ACCOUNT_SETUP) {
            $this->addTableCol(array('id'=>'account_id','type'=>'INTEGER','title'=>'Account',
                                     'join'=>'`title` FROM `'.TABLE_PREFIX.'account` WHERE `id`'));
        }
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Portfolio name'));
        $this->addTableCol(array('id'=>'description','type'=>'STRING','title'=>'Description','size'=>40,'required'=>false));
        $this->addTableCol(array('id'=>'date_start','type'=>'DATE','title'=>'Date start','new'=>date('Y-m-d')));
        $this->addTableCol(array('id'=>'date_end','type'=>'DATE','title'=>'Date end'));
        $this->addTableCol(array('id'=>'currency_id','type'=>'STRING','title'=>'Currency','new'=>CURRENCY_ID));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));

        //NB: CANNOT HAVE ANY ACCOUNT_ID FIELDS IN SEARCH OPTIONS
        $this->addSearch(array('name','description','date_start','date_ernd','status'),array('rows'=>2));
          
        $this->addSelect('status','(SELECT "OK") UNION (SELECT "INACTIVE")');
        $this->addSelect('currency_id','SELECT `currency_id`,`name` FROM `'.TABLE_PREFIX.'currency` ORDER BY `name`');

        if(ACCOUNT_SETUP) {
            $sql_account = 'SELECT id,CONCAT(IF(`level` > 1,REPEAT("--",`level` - 1),""),`title`) FROM `'.TABLE_PREFIX.'account`  ORDER BY `rank`';
            $this->addSelect('account_id',$sql_account);
        }
    }
     
}