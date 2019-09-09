<?php 
namespace App\Asset;

use Seriti\Tools\Table;

class AssetNote extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Note','col_label'=>'date','pop_up'=>true];
        parent::setup($param);        
                       
        //NB: specify master table relationship
        $this->setupMaster(array('table'=>TABLE_PREFIX.'asset','key'=>'asset_id','child_col'=>'location_id','child_prefix'=>'ASSET', 
                                 'show_sql'=>'SELECT CONCAT("'.$page_title.'",name) FROM '.TABLE_PREFIX.'asset WHERE asset_id = "{KEY_VAL}" '));  

        
        $this->addTableCol(array('id'=>'note_id','type'=>'INTEGER','title'=>'Note ID','key'=>true,'key_auto'=>true,'list'=>false));
        $this->addTableCol(array('id'=>'note','type'=>'TEXT','title'=>'Notes'));
        $this->addTableCol(array('id'=>'date','type'=>'DATETIME','title'=>'Note date','new'=>date('Y-m-d j:i')));

        $this->addSortOrder('T.date, T.note_id ','Note date','DEFAULT');

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));

        $this->addSearch(array('notes','date'),array('rows'=>1));
    }    
}

?>
