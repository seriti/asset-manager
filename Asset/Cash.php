<?php 
namespace App\Asset;

use Seriti\Tools\Table;

use App\Asset\Helpers;
use App\Asset\COMPANY_ID;
use App\Asset\TABLE_PREFIX;

class Cash extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Cashflow','col_label'=>'description'];
        parent::setup($param);  

        //$this->modifyAccess(['edit'=>false,'add'=>false]); 

        //adds these values to any new transactions
        $this->addColFixed(['id'=>'date_create','value'=>date('Y-m-d')]);

        $this->addTableCol(array('id'=>'transact_id','type'=>'INTEGER','title'=>'Transaction ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'portfolio_id','type'=>'INTEGER','title'=>'Portfolio',
                                 'join'=>'`name` FROM `'.TABLE_PREFIX.'portfolio` WHERE `portfolio_id`'));
        $this->addTableCol(array('id'=>'type_id','type'=>'STRING','title'=>'Type'));
        $this->addTableCol(array('id'=>'asset_id','type'=>'INTEGER','title'=>'Cashflow Asset',
                                 'join'=>'`name` FROM `'.TABLE_PREFIX.'asset` WHERE `asset_id`'));
        $this->addTableCol(array('id'=>'asset_id_link','type'=>'INTEGER','title'=>'Linked Asset',
                                 'join'=>'`name` FROM `'.TABLE_PREFIX.'asset` WHERE `asset_id`'));
        $this->addTableCol(array('id'=>'date','type'=>'DATE','title'=>'Transact Date','new'=>date('Y-m-d')));
        $this->addTableCol(array('id'=>'nominal','type'=>'DECIMAL','title'=>'Nominal'));
        $this->addTableCol(array('id'=>'description','type'=>'STRING','title'=>'Description','required'=>false));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        $this->addSortOrder('T.`date_create` DESC , T.`date` DESC , T.`transact_id` DESC ','Create Date, Transaction Date, most recent first','DEFAULT');

        $allow_types = '"'.implode(array_keys(CASHFLOW_TYPE),'","').'"';
        $this->addSql('WHERE','T.`type_id` IN('.$allow_types.')');

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));
        
        $this->addSearch(array('transact_id','date','amount','description',
                               'asset_id','type_id'),array('rows'=>2));
            
        $this->addSelect('status','(SELECT "NEW") UNION (SELECT "OK")');
        $this->addSelect('type_id',['list'=>CASHFLOW_TYPE]); 
        $this->addSelect('portfolio_id','SELECT `portfolio_id`,CONCAT(`name`," - ",`currency_id`) '.
                                        'FROM `'.TABLE_PREFIX.'portfolio` WHERE `status` = "OK" ORDER BY `name`');
        //NB: Trade class has <> "CASH" imperative that Transaction asset_id unique to Cashflows 
        $this->addSelect('asset_id','SELECT `asset_id`,`name` FROM `'.TABLE_PREFIX.'asset` WHERE `type_id` = "CASH" ORDER BY `name`');
        //related asset for INCOME and EXPENSE cashflows
        $this->addSelect('asset_id_link',['xtra'=>[0=>'NONE'],'sql'=>'SELECT `asset_id`,`name` FROM `'.TABLE_PREFIX.'asset` ORDER BY `name`']);  
        //WHERE type_id <> "CASH", need to allow for cash a count interest and fees
        
    }

    protected function beforeDelete($id,&$error_str) 
    {
        $error_tmp = '';
        
        $sql = 'SELECT * FROM `'.$this->table.'` '.
               'WHERE `transact_id` = "'.$this->db->escapeSql($id).'" ';
        $transact = $this->db->readSqlRecord($sql);
        Helpers::checkTransactionPeriod($this->db,$transact['portfolio_id'],$transact['date'],$error_tmp); 
        if($error_tmp !== '') {
            $error_str .= 'Cannot delete transaction: '.$error_tmp;
        }    
    }
    
    protected function beforeUpdate($id,$edit_type,&$form,&$error_str) 
    {
        Helpers::checkTransactionValid($this->db,'CASH',$form['portfolio_id'],$form,$error_tmp);
        if($error_tmp != '') $error_str .= 'Cannot '.$edit_type.' transaction: '.$error_tmp;
    }

    protected function modifyRowValue($col_id,$data,&$value) {
        if($col_id === 'type_id') {
            if(isset(CASHFLOW_TYPE[$value])) {
                $value = CASHFLOW_TYPE[$value];
            } 
        }  
        
    }
     
}