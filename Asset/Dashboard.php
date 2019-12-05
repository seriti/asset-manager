<?php
namespace App\Asset;

use Seriti\Tools\Dashboard AS DashboardTool;

use Seriti\Tools\GoogleChart;
use Seriti\Tools\CURRENCY_ID;

use App\Asset\Helpers;

class Dashboard extends DashboardTool
{
    
    public function setup($param = []) 
    {
        $this->col_count = 2;  

        $login_user = $this->getContainer('user'); 

        //(block_id,col,row,title)
        $this->addBlock('QUICK',1,1,'Quick links');
        $this->addItem('QUICK','Add an ASSET transaction',['link'=>'trade?mode=add']);
        $this->addItem('QUICK','Add a CASHFLOW transaction',['link'=>'cash?mode=add']);
        
        if($login_user->getAccessLevel() === 'GOD') {
            $this->addItem('QUICK','Setup Database',['link'=>'setup_data','icon'=>'setup']);
        }    
        

        $this->addBlock('CHART',2,1,'Portfolio performance and asset holdings'); 
        $this->addItem('CHART','<div id="value_chart"></div>');
        $this->addItem('CHART','<div id="perform_chart"></div>');
        $this->addItem('CHART','<div id="asset_chart"></div>');
        //style="width: 600px; height: 300px;"
        

        
    }

    public function getJavaScript() {
        $js = '';
        $error = '';
        $options = [];
        
        //get stats for ALL portfolios
        $stats = Helpers::getDashboardStats($this->db,CURRENCY_ID,$options,$error);
        
        if($error === '') {
            $charts = new GoogleChart();

            $data = $stats['perform'];
            $param = ['width'=>600,'height'=>300,
                      'series'=>$stats['series'],
                      'stacked'=>false,
                      'y_axis'=>'% return for month'];
            $div_id = 'perform_chart';
            $charts->addBarChart($div_id,'Portfolio performance in '.CURRENCY_ID,$data,$param);

            $data = $stats['value'];
            $param = ['width'=>600,'height'=>300,
                      'series'=>$stats['series'],
                      'stacked'=>false,
                      'y_axis'=>'Portfolio value'];
            $div_id = 'value_chart';
            $charts->addBarChart($div_id,'Portfolio value in '.CURRENCY_ID,$data,$param);
            
            $data = $stats['assets'];
            $param = ['width'=>600,'height'=>300,'3D'=>true];
            $div_id = 'asset_chart';
            $charts->addPieChart($div_id,'ALL current asset values in '.CURRENCY_ID,$data,$param);

            $js = $charts->getJavaScript();
        } else {
            if(DEBUG) $js = $error;
        }       
        

        return $js;
        
    }    

}