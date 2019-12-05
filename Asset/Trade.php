<?php 
namespace App\Asset;

use Seriti\Tools\Table;

use App\Asset\Helpers;
use App\Asset\TABLE_PREFIX;

class Trade extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Trade','col_label'=>'description'];
        parent::setup($param);  

        $this->modifyAccess(['edit'=>false,'add'=>false]); 

        //adds these values to any new transactions
        $this->addColFixed(['id'=>'date_create','value'=>date('Y-m-d')]);

        $this->addTableCol(array('id'=>'transact_id','type'=>'INTEGER','title'=>'Transaction ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'portfolio_id','type'=>'INTEGER','title'=>'Portfolio','join'=>'name FROM '.TABLE_PREFIX.'portfolio WHERE portfolio_id'));
        $this->addTableCol(array('id'=>'type_id','type'=>'STRING','title'=>'Type'));
        $this->addTableCol(array('id'=>'asset_id','type'=>'INTEGER','title'=>'Transaction Asset','join'=>'CONCAT(name," - ",currency_id) FROM '.TABLE_PREFIX.'asset WHERE asset_id'));
        $this->addTableCol(array('id'=>'asset_id_link','type'=>'INTEGER','title'=>'Cash asset counterparty','join'=>'CONCAT(name," - ",currency_id) FROM '.TABLE_PREFIX.'asset WHERE asset_id'));
        $this->addTableCol(array('id'=>'date','type'=>'DATE','title'=>'Transact Date','new'=>date('Y-m-d')));
        $this->addTableCol(array('id'=>'nominal','type'=>'DECIMAL','title'=>'Nominal'));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Price'));
        $this->addTableCol(array('id'=>'amount','type'=>'DECIMAL','title'=>'Amount'));
        $this->addTableCol(array('id'=>'description','type'=>'STRING','title'=>'Description','required'=>false));
        
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        $this->addSortOrder('T.date_create DESC , T.date DESC , T.transact_id DESC ','Create Date, Transaction Date, most recent first','DEFAULT');

        $allow_types = '"'.implode(array_keys(TRADE_TYPE),'","').'"';
        $this->addSql('WHERE','T.type_id IN('.$allow_types.')');

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));
        
        $this->addSearch(array('transact_id','date','amount','description',
                               'asset_id','type_id'),array('rows'=>2));
            
        $this->addSelect('status','(SELECT "NEW") UNION (SELECT "OK")');
        $this->addSelect('type_id',['list'=>TRADE_TYPE]); 
        $this->addSelect('portfolio_id','SELECT portfolio_id,CONCAT(name," - ",currency_id) FROM '.TABLE_PREFIX.'portfolio WHERE status = "OK" ORDER BY name');
        //NB: Cash class has = "CASH" imperative that Transaction asset_id unique to Trades
        $this->addSelect('asset_id','SELECT asset_id,CONCAT(name," - ",currency_id) FROM '.TABLE_PREFIX.'asset WHERE type_id <> "CASH" ORDER BY name');
        //Where cash comesfrom(BUY) or goes to(SELL) 
        $this->addSelect('asset_id_link','SELECT asset_id,CONCAT(name," - ",currency_id)  FROM '.TABLE_PREFIX.'asset WHERE type_id = "CASH" ORDER BY name'); 
        
    }

    protected function beforeDelete($id,&$error_str) 
    {
        $error_tmp = '';
        
        $sql = 'SELECT * FROM '.$this->table.' '.
               'WHERE transact_id = "'.$this->db->escapeSql($id).'" ';
        $transact = $this->db->readSqlRecord($sql);
        Helpers::checkTransactionPeriod($this->db,$transact['portfolio_id'],$transact['date'],$error_tmp); 
        if($error_tmp !== '') {
            $error_str .= 'Cannot delete transaction: '.$error_tmp;
        }    
    }
    
    protected function beforeUpdate($id,$edit_type,&$form,&$error_str) 
    {
        $error_tmp = '';
        
        //adjustments have NO counterparty
        if($form['type_id'] === 'ADJUST')  {
            $form['asset_id2'] = 0;
        }
        
        Helpers::checkTransactionValid($this->db,'TRADE',$form['portfolio_id'],$form,$error_tmp);
        if($error_tmp != '') $error_str .= 'Cannot '.$edit_type.' transaction: '.$error_tmp;
    }

    //add transaction price to prices if non exist for transaction month
    protected function afterUpdate($id,$edit_type,$data) 
    {
        $error = '';
        Helpers::updatePrices($this->db,$data['portfolio_id'],$data,$error);
    }

    protected function modifyRowValue($col_id,$data,&$value) {
        if($col_id === 'type_id') {
            if(isset(TRADE_TYPE[$value])) {
                $value = TRADE_TYPE[$value];
            } 
        }  
        
    }
     
}