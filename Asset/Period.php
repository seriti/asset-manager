<?php 
namespace App\Asset;

use Seriti\Tools\Table;
use Seriti\Tools\Date;

use App\Asset\TABLE_PREFIX;
use App\Asset\Helpers;


class Period extends Table 
{
    //configure
    public function setup($param = []) 
    {
        $param = ['row_name'=>'Period','col_label'=>'name'];
        parent::setup($param);        

           
        $this->addTableCol(array('id'=>'period_id','type'=>'INTEGER','title'=>'Period ID','key'=>true,'key_auto'=>true,'list'=>true));
        $this->addTableCol(array('id'=>'portfolio_id','type'=>'INTEGER','title'=>'Portfolio','join'=>'name FROM '.TABLE_PREFIX.'portfolio WHERE portfolio_id'));
        $this->addTableCol(array('id'=>'name','type'=>'STRING','title'=>'Period name'));
        $this->addTableCol(array('id'=>'date_start','type'=>'DATE','title'=>'Date START'));
        $this->addTableCol(array('id'=>'date_end','type'=>'DATE','title'=>'Date END'));
        $this->addTableCol(array('id'=>'status','type'=>'STRING','title'=>'Status'));
        
        $this->addSortOrder('T.date_start ','Start date','DEFAULT');

        $this->addAction(array('type'=>'edit','text'=>'edit'));
        $this->addAction(array('type'=>'delete','text'=>'delete','pos'=>'R'));

        $this->addSearch(array('name','date_start','status'),array('rows'=>2));
         
        $this->addSelect('portfolio_id','SELECT portfolio_id,CONCAT(name," - ",currency_id) FROM '.TABLE_PREFIX.'portfolio WHERE status = "OK" ORDER BY name');  
        $this->addSelect('status','(SELECT "OPEN") UNION (SELECT "CLOSED")');
    }
   
    protected function beforeUpdate($id,$edit_type,&$form,&$error_str) {
        $error_str = '';
        $date_options['include_first'] = False;
        $days = 0;
        
        //check periods dates in sequence
        $days = Date::calcDays($form['date_start'],$form['date_end'],'MYSQL',$date_options);
        if($days < 30) $error_str .= 'Period end date must be at least 30 days after start date'; 
    }
}