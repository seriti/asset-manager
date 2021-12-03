<?php 
namespace App\Asset;

use Seriti\Tools\Table;

use App\Asset\TABLE_PREFIX;

class Asset extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Asset','col_label'=>'name'];
        parent::setup($param);        

        $this->addForeignKey(array('table'=>TABLE_PREFIX.'transact','col_id'=>'asset_id','message'=>'Transactions exist for this Asset'));  

        $this->addTableCol(array('id'=>'asset_id','type'=>'INTEGER','title'=>'Asset ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Asset name'));
        $this->addTableCol(array('id'=>'type_id','type'=>'STRING','title'=>'Type'));
        $this->addTableCol(array('id'=>'currency_id','type'=>'STRING','title'=>'Currency'));
        $this->addTableCol(array('id'=>'description','type'=>'STRING','title'=>'Description','size'=>40,'required'=>false));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));

        $this->addSortOrder('T.`type_id`, T.`name`','Type & Name','DEFAULT');

        $this->addAction(array('type'=>'check_box','text'=>''));
        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));
        $this->addAction(array('type'=>'popup','text'=>'Notes','url'=>'asset_note','mode'=>'view','width'=>600,'height'=>600)); 
        $this->addAction(array('type'=>'popup','text'=>'Prices','url'=>'asset_price','mode'=>'view','width'=>600,'height'=>600,'verify'=>true)); 

        $this->addSearch(array('asset_id','name','type_id','description','currency_id','status'),array('rows'=>2));
            
        $this->addSelect('status','(SELECT "OK") UNION (SELECT "HIDE")');
        $this->addSelect('type_id',array('list'=>ASSET_TYPE));
        $this->addSelect('currency_id','SELECT `currency_id`,`name` FROM `'.TABLE_PREFIX.'currency` ORDER BY `name`');

        $this->setupImages(array('table'=>TABLE_PREFIX.'file','location'=>'ASSIMG','max_no'=>100,
                                  'icon'=>'<span class="glyphicon glyphicon-picture" aria-hidden="true"></span>&nbsp;manage',
                                  'list'=>true,'list_no'=>1,'storage'=>STORAGE,
                                  'link_url'=>'asset_image','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));

                                  
        $this->setupFiles(array('table'=>TABLE_PREFIX.'file','location'=>'ASSDOC','max_no'=>100,
                                'icon'=>'<span class="glyphicon glyphicon-folder-open" aria-hidden="true"></span>&nbsp;&nbsp;manage',
                                'list'=>true,'list_no'=>5,'storage'=>STORAGE,'search'=>true,
                                'link_url'=>'asset_file','link_data'=>'SIMPLE','width'=>'700','height'=>'600'));
    }

    protected function modifyRowValue($col_id,$data,&$value) {
        if($col_id === 'type_id') {
            if(isset(ASSET_TYPE[$value])) {
                $value = ASSET_TYPE[$value];
            } 
        }  
        
    } 

    protected function verifyRowAction($action,$data) {
        if($action['url'] === 'asset_price') {
            //CASH assets do not have prices
            if($data['type_id'] === 'CASH') return false;
        }
        return true;
    }

    protected function afterDelete($id) {
        $error = '';
        //remove all asset related data
        $sql = 'DELETE FROM `'.TABLE_PREFIX.'price` WHERE `asset_id` = "'.$this->db->escapeSql($id).'" ';
        $this->db->executeSql($sql,$error);

        $sql = 'DELETE FROM `'.TABLE_PREFIX.'note` WHERE `location_id` = "ASSET'.$this->db->escapeSql($id).'" ';
        $this->db->executeSql($sql,$error);
    } 
} 