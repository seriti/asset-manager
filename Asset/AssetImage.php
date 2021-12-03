<?php 
namespace App\Asset;

use Seriti\Tools\Upload;
use Seriti\Tools\STORAGE;
use Seriti\Tools\BASE_PATH;
use Seriti\Tools\BASE_UPLOAD;

class AssetImage extends Upload 
{
  //configure
    public function setup($param = []) 
    {
        $id_prefix = 'ASSIMG'; 

        $param = ['row_name'=>'Asset image',
                  'pop_up'=>true,
                  'col_label'=>'file_name_orig',
                  'update_calling_page'=>true,
                  'prefix'=>$id_prefix,//will prefix file_name if used, but file_id.ext is unique 
                  'upload_location'=>$id_prefix]; 
        parent::setup($param);

        //limit to web viewable images
        $this->allow_ext = array('Images'=>array('jpg','jpeg','gif','png')); 

        $param = [];
        $param['table']     = TABLE_PREFIX.'asset';
        $param['key']       = 'asset_id';
        $param['label']     = 'name';
        $param['child_col'] = 'location_id';
        $param['child_prefix'] = $id_prefix;
        $param['show_sql'] = 'SELECT CONCAT("Asset: ",`name`) FROM `'.TABLE_PREFIX.'asset` WHERE `asset_id` = "{KEY_VAL}"';
        $this->setupMaster($param);

        $this->addAction('check_box');
        $this->addAction('edit');
        $this->addAction(['type'=>'delete','text'=>'Delete','pos'=>'R']);
        
        //$access['read_only'] = true;                         
        //$this->modifyAccess($access);
    }
}