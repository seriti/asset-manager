<?php
namespace App\Asset;

use Seriti\Tools\CURRENCY_ID;
use Seriti\Tools\Form;
use Seriti\Tools\Report AS ReportTool;

class Report extends ReportTool
{
     

    //configure
    public function setup() 
    {
        //$this->report_header = 'WTF';
        $param = [];
        $this->report_select_title = 'Select report';
        $this->always_list_reports = true;

        $param = ['input'=>['select_portfolio','select_currency','select_month_period']];
        $this->addReport('PERFORMANCE','Monthly performance',$param); 
        $this->addReport('PERFORMANCE_CHART','Monthly performance chart',$param);

        $param = ['input'=>['select_portfolio','select_currency','select_period']];
        $this->addReport('PERFORMANCE_PERIOD','Monthly performance over period',$param);
        
        $param = ['input'=>['select_portfolio','select_currency','select_month_period']];
        $this->addReport('ASSET_BALANCES','Monthly asset balances',$param);  
        $this->addReport('ASSET_BALANCES_CHART','Monthly asset balances chart',$param);

        $param = ['input'=>['select_portfolio','select_currency','select_period']];
        $this->addReport('ASSET_BALANCES_PERIOD','Monthly asset balances over period',$param);

        $this->addInput('select_portfolio','');
        $this->addInput('select_currency',''); //Combine All portfolios
        $this->addInput('select_month_period',''); //Select report months
        $this->addInput('select_period',''); //Select report period
        $this->addInput('select_format',''); //Select Report format
    }

    protected function viewInput($id,$form = []) 
    {
        $html = '';
        
        if($id === 'select_portfolio') {
            $param = [];
            $param['class'] = 'form-control input-medium';
            $param['xtra'] = ['ALL'=>'All portfolios'];
            $sql = 'SELECT portfolio_id,name FROM '.TABLE_PREFIX.'portfolio WHERE status = "OK" ORDER BY name'; 
            if(isset($form['portfolio_id'])) $portfolio_id = $form['portfolio_id']; else $portfolio_id = 'ALL';
            $html .= Form::sqlList($sql,$this->db,'portfolio_id',$portfolio_id,$param);
        }

        if($id === 'select_currency') {
            $param = [];
            $param['class'] = 'form-control input-medium';
            $sql = 'SELECT currency_id,CONCAT("In: ",name) FROM '.TABLE_PREFIX.'currency ORDER BY name'; 
            if(isset($form['currency_id'])) $currency_id = $form['currency_id']; else $currency_id = CURRENCY_ID;
            $html .= Form::sqlList($sql,$this->db,'currency_id',$currency_id,$param);
        }

        if($id === 'select_date_from') {
            $param = [];
            $param['class'] = $this->classes['date'];
            if(isset($form['date_from'])) $date_from = $form['date_from']; else $date_from = date('Y-m-d',mktime(0,0,0,date('m')-12,date('j'),date('Y')));
            $html .= Form::textInput('date_from',$date_from,$param);
        }

        if($id === 'select_date_to') {
            $param = [];
            $param['class'] = $this->classes['date'];
            if(isset($form['date_to'])) $date_to = $form['date_to']; else $date_to = date('Y-m-d');
            $html .= Form::textInput('date_to',$date_to,$param);
        }      

        if($id === 'select_month_period') {
            $past_years = 10;
            $future_years = 0;

            $param = [];
            $param['class'] = 'form-control input-small input-inline';
            
            $html .= 'From:';
            if(isset($form['from_month'])) $from_month = $form['from_month']; else $from_month = 1;
            if(isset($form['from_year'])) $from_year = $form['from_year']; else $from_year = date('Y');
            $html .= Form::monthsList($from_month,'from_month',$param);
            $html .= Form::yearsList($from_year,$past_years,$future_years,'from_year',$param);
            $html .= '&nbsp;&nbsp;To:';
            if(isset($form['to_month'])) $to_month = $form['to_month']; else $to_month = date('m');
            if(isset($form['to_year'])) $to_year = $form['to_year']; else $to_year = date('Y');
            $html .= Form::monthsList($to_month,'to_month',$param);
            $html .= Form::yearsList($to_year,$past_years,$future_years,'to_year',$param);
        }

        if($id === 'select_period') {
            $param = [];
            $param['class'] = 'form-control input-large';
            $sql = 'SELECT period_id,CONCAT(name," : ",date_start," -> ",date_end) as period_name '.
                   'FROM '.TABLE_PREFIX.'period '.
                   'ORDER BY date_start '; 
            if(isset($form['period_id'])) $period_id = $form['period_id']; else $period_id = '';
            $html .= Form::sqlList($sql,$this->db,'period_id',$period_id,$param);
        }

        if($id === 'select_format') {
            if(isset($form['format'])) $format = $form['format']; else $format = 'HTML';
            $html.= Form::radiobutton('format','PDF',$format).'&nbsp;<img src="/images/pdf_icon.gif">&nbsp;PDF document<br/>';
            $html.= Form::radiobutton('format','CSV',$format).'&nbsp;<img src="/images/excel_icon.gif">&nbsp;CSV/Excel document<br/>';
            $html.= Form::radiobutton('format','HTML',$format).'&nbsp;Show on page<br/>';
        }

        return $html;       
    }

    protected function processReport($id,$form = []) 
    {
        $html = '';
        $error = '';
        $options = [];
        //$options['format'] = $form['format'];

        if($form['portfolio_id'] === 'ALL') {
            $html .= '<h2>(ALL PORTFOLIOS, values expressed in currency - '.$form['currency_id'].')</h2>';
        } else {
            $portfolio = Helpers::getPortfolio($this->db,$form['portfolio_id']);
            $html .= '<h2>('.$portfolio['name'].', values expressed in currency - '.$form['currency_id'].')</h2>';
        }    
        
        if($id === 'PERFORMANCE') {
            $html .= Helpers::performanceReport($this->db,$form['portfolio_id'],$form['currency_id'],$form['from_month'],$form['from_year'],$form['to_month'],$form['to_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'PERFORMANCE_PERIOD') {
            $period = Helpers::getPeriod($this->db,$form['period_id']);
            $html .= Helpers::performanceReport($this->db,$form['portfolio_id'],$form['currency_id'],$period['start_month'],$period['start_year'],$period['end_month'],$period['end_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'PERFORMANCE_CHART') {
            $html .= Helpers::getPortfolioChart($this->db,'performance',$form['portfolio_id'],$form['currency_id'],$form['from_month'],$form['from_year'],$form['to_month'],$form['to_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }
        

        if($id === 'ASSET_BALANCES') {
            $html .= Helpers::assetBalancesReport($this->db,$form['portfolio_id'],$form['currency_id'],$form['from_month'],$form['from_year'],$form['to_month'],$form['to_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'ASSET_BALANCES_PERIOD') {
            $period = Helpers::getPeriod($this->db,$form['period_id']);
            $html .= Helpers::assetBalancesReport($this->db,$form['portfolio_id'],$form['currency_id'],$period['start_month'],$period['start_year'],$period['end_month'],$period['end_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        if($id === 'ASSET_BALANCES_CHART') {
            $html .= Helpers::getPortfolioChart($this->db,'assets',$form['portfolio_id'],$form['currency_id'],$form['from_month'],$form['from_year'],$form['to_month'],$form['to_year'],$options,$error);
            if($error !== '') $this->addError($error);
        }

        return $html;
    }

}