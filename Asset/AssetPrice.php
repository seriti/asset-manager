<?php 
namespace App\Asset;

use Seriti\Tools\Table;

use App\Asset\TABLE_PREFIX;

class AssetPrice extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Price','col_label'=>'date','pop_up'=>true];
        parent::setup($param);        

        //NB: specify master table relationship
        $this->setupMaster(array('table'=>TABLE_PREFIX.'asset','key'=>'asset_id','child_col'=>'asset_id',
                                 'show_sql'=>'SELECT CONCAT("Prices for: ",`name`," in <strong>",currency_id,"</strong>") '.
                                             'FROM `'.TABLE_PREFIX.'asset` WHERE `asset_id` = "{KEY_VAL}" '));  

        $this->addTableCol(array('id'=>'price_id','type'=>'INTEGER','title'=>'Price ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'year','type'=>'INTEGER','title'=>'Year','new'=>date('Y')));
        $this->addTableCol(array('id'=>'month','type'=>'INTEGER','title'=>'Month','new'=>date('m')));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Asset price'));
        
        $this->addSortOrder('T.`Year` DESC, T.`Month` DESC','Most recent first','DEFAULT');

        $this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));
        
        $this->addSearch(array('year','month','price'),array('rows'=>1));

        $this->addSelect('asset_id','SELECT `asset_id`,`name` FROM `'.TABLE_PREFIX.'asset` WHERE `type_id` <> "CASH" ORDER BY `name`'); 
        $this->addSelect('month',['list'=>MONTH_LIST]); 
    }
} 