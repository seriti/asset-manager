<?php 
namespace App\Asset;

use Seriti\Tools\Table;

use App\Asset\TABLE_PREFIX;

class Price extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Price','col_label'=>'date'];
        parent::setup($param);        

        $this->addTableCol(array('id'=>'price_id','type'=>'INTEGER','title'=>'Price ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'asset_id','type'=>'INTEGER','title'=>'Asset',
                                 'join'=>'`name` FROM `'.TABLE_PREFIX.'asset` WHERE `asset_id`'));
        $this->addTableCol(array('id'=>'year','type'=>'INTEGER','title'=>'Year','new'=>date('Y')));
        $this->addTableCol(array('id'=>'month','type'=>'INTEGER','title'=>'Month','new'=>date('m')));
        $this->addTableCol(array('id'=>'price','type'=>'DECIMAL','title'=>'Asset price'));
        
        $this->addSortOrder('T.Year DESC, T.Month DESC','Most recent first','DEFAULT');

        $this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));
        
        $this->addSearch(array('asset_id','year','month','price'),array('rows'=>1));

        $this->addSelect('asset_id','SELECT `asset_id`,`name` FROM `'.TABLE_PREFIX.'asset` WHERE `type_id` <> "CASH" ORDER BY `name`'); 
    }
}