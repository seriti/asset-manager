<?php
namespace App\Asset;

use Exception;
use Seriti\Tools\Calc;
use Seriti\Tools\Csv;
use Seriti\Tools\Html;
use Seriti\Tools\Pdf;
use Seriti\Tools\Doc;
use Seriti\Tools\Date;
use Seriti\Tools\Validate;
use Seriti\Tools\GoogleChart;
use Seriti\Tools\BASE_UPLOAD;
use Seriti\Tools\UPLOAD_DOCS;
use Seriti\Tools\STORAGE;


class Helpers {
    
    public static function getDashboardStats($db,$currency_id,$options = [],&$error) {
        $error = '';
        $stats = [];
        $perform = [];
        $assets = [];

        $account_id = 'ALL';
        $portfolio_id = 'ALL';

        if(!isset($options['no_months'])) {
            $period = self::getTransactionPeriod($db,$portfolio_id);
            if($period['hist_months'] > 12) $options['no_months'] = 12; else $options['no_months'] = $period['hist_months'];
        } 

        $date = getdate();
        $to['month'] = $date['mon'];
        $to['year'] = $date['year'];
        $from = Date::incrementMonthYear($to['month'],$to['year'],-($options['no_months']-1));
        
        $months = self::getMonthlySequence($from['month'],$from['year'],$to['month'],$to['year']);
        $m = 0;
        foreach($months as $month) {
            $m++;
            $key = $month['year'].':'.$month['mon'];
            $perform[] = [$key];
        }
       
        $options_pf = ['output'=>'DATA'];
        $error_pf = '';
        $perform = self::getPortfolioChart($db,'performance',$account_id,$portfolio_id,$currency_id,$from['month'],$from['year'],$to['month'],$to['year'],$options_pf,$error_pf);
        if($error_pf !== '') {
            $error .= 'No performance data available: '.$error_pf;
        } else {   
            $data = self::assetBalancesReport($db,$account_id,$portfolio_id,$currency_id,$to['month'],$to['year'],$to['month'],$to['year'],$options_pf,$error_pf); 
            if($error_pf !== '') {
                $error .= 'No asset balances available: '.$error_pf;
            } else {  
                $key = $to['year'].':'.$to['month'];
                foreach($data[$key] as $asset) {                   
                    $assets[$asset['name']] = $asset['end_value'];
                }
            } 
            
            $stats['series'] = $perform['series'];
            //portfolio monthly %return
            $stats['perform'] = $perform['data'];
            //portfolio monthly value
            $stats['value'] = $perform['data2'];
            $stats['assets'] = $assets;
        }

        return $stats;
    }

    public static function getPortfolioChart($db,$type,$account_id,$portfolio_id,$currency_id,$from_month,$from_year,$to_month,$to_year,$options = [],&$error) {
        $error = '';
         
        //NB $options['width'] and $options['height'] will use container if not specified
        if(!isset($options['output'])) $options['output'] = 'HTML';

        if($type === 'assets') {
            $chart_type = 'bar';
            $assets = self::getPortfolioAssets($db,$account_id,$portfolio_id,$error);
            $title = 'Monthly asset values';
            $y_axis = 'asset value in '.$currency_id;
            $stacked = true;
        }
        
        if($type === 'performance') {
            $chart_type = 'bar';
            $title = 'Monthly performance percentage';
            $y_axis = '% return for month';
            //if($portfolio_id === 'ALL') $stacked = true; else $stacked = false;
            $stacked = false;
        }    
        
        //get outta here if basic error found
        if($error !== '') return false;

        $chart_data = [];
        $chart_data2 = [];
        $chart_series = ['month'];

        if($type === 'assets') {
            foreach($assets as $asset) {
                $series = $asset['name'];
                if($portfolio_id === 'ALL') $series .= '('.$asset['portfolio'].')';
                $chart_series[] = $series;
            }    
        }    

        $months = self::getMonthlySequence($from_month,$from_year,$to_month,$to_year);
        $m = 0;
        foreach($months as $month) {
            $key = $month['year'].':'.$month['mon'];
            $chart_data[$m] = [$key];
            $chart_data2[$m] = [$key];
            $m++;
        }

        if($portfolio_id === 'ALL') {            
            $sql = 'SELECT portfolio_id,name FROM '.TABLE_PREFIX.'portfolio  WHERE status = "OK"';
        } else {
            $sql = 'SELECT portfolio_id,name FROM '.TABLE_PREFIX.'portfolio  WHERE portfolio_id = "'.$db->escapeSql($portfolio_id).'"';
        }
        $portfolios = $db->readSqlList($sql);    

        if($portfolios == 0) {
            $error = 'NO portfolios found to chart.';
        } else {    
            //need to combine multiple portfolios data
            $options_pf = ['output'=>'DATA'];
            //pf_id specified so account ignored
            $acc_id_tmp = 'ALL';
            
            foreach($portfolios as $pf_id=>$name) {
                $error_pf = '';
                
                if($type === 'performance') {
                    $chart_series[] = $name;

                    //need to add account_id
                    $data = self::performanceReport($db,$acc_id_tmp,$pf_id,$currency_id,$from_month,$from_year,$to_month,$to_year,$options_pf,$error_pf);
                    $m = 0;
                    foreach($months as $month) {
                        $key = $month['year'].':'.$month['mon'];
                        if($error_pf === '') {
                            $return = $data[$key]['return'];
                            $value = $data[$key]['end_value'];
                        } else {
                            $return = 0;
                            $value = 0;
                        }    
                        $chart_data[$m][] = $return;
                        $chart_data2[$m][] = $value;
                        $m++;
                    }
                    
                }    

                if($type === 'assets') {
                    $data = self::assetBalancesReport($db,$acc_id_tmp,$pf_id,$currency_id,$from_month,$from_year,$to_month,$to_year,$options_pf,$error_pf);
                    $m = 0;
                    foreach($months as $month) {
                        $key = $month['year'].':'.$month['mon'];
                        
                        //NB: $a = 0 is occupied by month key
                        $a = 1; 
                        foreach($assets as $asset_id => $asset) {

                            if($error_pf === '' and isset($data[$key][$asset_id])) {
                                $holding = $data[$key][$asset_id]['end_value'];
                            } else {
                                $holding = 0;
                            }    
                            
                            if(isset($chart_data[$m][$a])) {
                                $chart_data[$m][$a] += $holding;
                            } else {
                                $chart_data[$m][$a] = $holding;
                            }
                            
                            $a++;
                        }
                        $m++;
                    }
                   
                }    
            }
        }


        if($error === '') {
            if($options['output'] === 'HTML') {
                $html = '';
                //construct necessary javascript
                $charts = new GoogleChart();
                $div_id = 'portfolio_chart';

                if(isset($options['width'])) $param['width'] = $options['width'];
                if(isset($options['height'])) $param['height'] = $options['height'];

                if($chart_type === 'bar') {
                    $param['series'] = $chart_series;
                    $param['stacked'] = $stacked;
                    $param['y_axis'] = $y_axis;
                    $charts->addBarChart($div_id,$title,$chart_data,$param);
                } 
                
                $js = $charts->getJavaScript();

                $html .= '<div id="'.$div_id .'" style="width: 100%; height: 600px;"></div>'.$js;

                //$html .= print_r($chart_data);
                return $html;
            }

            if($options['output'] === 'DATA') {
                $chart = [];
                $chart['series'] = $chart_series;
                $chart['data'] = $chart_data;
                $chart['data2'] = $chart_data2;

                return $chart;
            }    
        }

        return false;
    }  

    //NB: Account table is a tree
    public static function getAccount($db,$account_id) {
        $sql = 'SELECT id,parent_id,title AS name,level,lineage,currency_id '.
               'FROM '.TABLE_PREFIX.'account '.
               'WHERE id = "'.$db->escapeSql($account_id).'" ';
        $account = $db->readSqlRecord($sql);
        if($account == 0) throw new Exception('ACCOUNT_HELPER_ERROR: INVALID Account ID['.$account_id.']');
        
        return $account;
    } 

    public static function getPortfolio($db,$portfolio_id) {
        $sql = 'SELECT portfolio_id AS id,name,description,status,date_start,date_end,currency_id,calc_timestamp '.
               'FROM '.TABLE_PREFIX.'portfolio '.
               'WHERE portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        $portfolio = $db->readSqlRecord($sql);
        if($portfolio == 0) throw new Exception('PORTFOLIO_HELPER_ERROR: INVALID Portfolio ID['.$portfolio_id.']');
        
        return $portfolio;
    } 

    public static function getTransactionPeriod($db,$portfolio_id) {
        $period = [];

        $sql = 'SELECT MAX(T.date) as max_date, MIN(T.date) as min_date '.
               'FROM  '.TABLE_PREFIX.'transact AS T ';
        if($portfolio_id !== 'ALL') {
            $sql .= 'WHERE T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" '.
                    'GROUP BY T.portfolio_id ';
        }
        
        $transact = $db->readSqlRecord($sql);

       if($transact != 0) {
            $from = Date::mysqlGetDate($transact['min_date']);
            $period['from_month'] = $from['mon'];
            $period['from_year'] = $from['year'];
            
            $to = Date::mysqlGetDate($transact['max_date']);
            $period['to_month'] = $to['mon'];
            $period['to_year'] = $to['year'];

            $today = getdate();
            $period['hist_months'] = Date::getNoMonths($period['from_month'],$period['from_year'],$today['mon'],$today['year']); 
        } 

        return $period;
    }   

    public static function getPeriod($db,$period_id) {
        $sql = 'SELECT period_id,portfolio_id,name,date_start,date_end,status '.
               'FROM '.TABLE_PREFIX.'period '.
               'WHERE period_id = "'.$db->escapeSql($period_id).'" ';
        $period = $db->readSqlRecord($sql);
        if($period == 0) throw new Exception('PERIOD_HELPER_ERROR: INVALID Period ID['.$period_id.']');
        
        $date = Date::getDate($period['date_start']);
        $period['start_year'] = $date['year'];
        $period['start_month'] = $date['mon'];

        $date = Date::getDate($period['date_end']);
        $period['end_year'] = $date['year'];
        $period['end_month'] = $date['mon'];

        return $period;
    }  

    public static function getAsset($db,$asset_id) {
        $sql = 'SELECT asset_id,portfolio_id,name,type_id,currency_id,status '.
               'FROM '.TABLE_PREFIX.'asset '.
               'WHERE asset_id = "'.$db->escapeSql($asset_id).'" ';
        $asset = $db->readSqlRecord($sql);
        if($asset == 0) throw new Exception('ASSET_HELPER_ERROR: INVALID Asset ID['.$asset_id.']');
        
        return $asset;
    }  

    //creates portfolio asset price record for transaction month if none exists
    public static function updatePrices($db,$portfolio_id,$transaction = [],&$error)  {
        $error = '';

        $date = Date::getDate($transaction['date']);
        $sql = 'SELECT * FROM '.TABLE_PREFIX.'price '.
               'WHERE asset_id = "'.$db->escapeSql($transaction['asset_id']).'" AND '.
                     'year = "'.$date['year'].'" AND month = "'.$date['mon'].'" ';
        $price = $db->readSqlRecord($sql);
        if($price === 0) {
            $data['year'] = $date['year'];
            $data['month'] = $date['mon'];
            $data['asset_id'] = $transaction['asset_id'];
            $data['price'] = $transaction['price'];

            $db->insertRecord(TABLE_PREFIX.'price',$data,$error);
        }      
    }

    public static function validateDateInterval($date_from,$date_to,&$error)  {
       $error = '';
       $error_tmp = '';

       if(!Validate::date('Start date',$date_from,'YYYY-MM-DD',$error_tmp)) {
          $error .= $error_tmp;
       }

       if(!Validate::date('End date',$date_to,'YYYY-MM-DD',$error_tmp)) {
          $error .= $error_tmp;
       }

       if($error === '') {
           $from = Date::getDate($date_from); 
           $to = Date::getDate($date_to); 

           if($from[0] >= $to[0]) {
              $error = 'Start date['.$date_from.'] cannot be after End date['.$date_to.']';
           } 
       }
    }

    public static function validateMonthInterval($from_month,$from_year,$to_month,$to_year,&$error)  {
       $error = '';
       $error_tmp = '';

       if($from_month < 1 or $from_month > 12) {
          $error .= 'From month['.$from_month.'] is not valid month number.' ;
       }

       if($from_year < 1900 or $from_year > 2100) {
          $error .= 'From year['.$from_year.'] is not valid year.' ;
       }

       if($to_month < 1 or $to_month > 12) {
          $error .= 'To month['.$to_month.'] is not valid month number.' ;
       }

       if($to_year < 1900 or $to_year > 2100) {
          $error .= 'To year['.$to_year.'] is not valid year.' ;
       }

       if($error === '') {
           $from_count = $from_year*12 + $from_month;
           $to_count = $to_year*12 + $to_month;

           if($from_count > $to_count) {
              $error = 'From month['.$from_year.':'.$from_month.'] cannot be after To month['.$to_year.':'.$to_month.']';
           } 
       }
    }

    //NB: returns all assets that have transactions, not all assets
    public static function getPortfolioAssets($db,$account_id,$portfolio_id,&$error)  {
        $error = '';
       
        $sql = 'SELECT DISTINCT(T.asset_id),A.type_id,A.name,A.currency_id,P.name AS portfolio '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
                      'JOIN '.TABLE_PREFIX.'asset AS A ON(T.asset_id = A.asset_id) '.
                      'JOIN '.TABLE_PREFIX.'portfolio AS P ON(T.portfolio_id = P.portfolio_id) '; 
        if($portfolio_id !== 'ALL') {
            $sql .= 'WHERE T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        } elseif($account_id !== 'ALL') {
            $sql .= 'JOIN '.TABLE_PREFIX.'account AS ACC ON(P.account_id = ACC.id) '.
                    'WHERE (P.account_id = "'.$db->escapeSql($account_id).'" OR FIND_IN_SET("'.$db->escapeSql($account_id).'",ACC.lineage) > 0) ';
        }

        /*
        $sql = 'SELECT DISTINCT(T.asset_id),A.type_id,A.name,A.currency_id,P.name AS portfolio '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
                      'JOIN '.TABLE_PREFIX.'asset AS A ON(T.asset_id = A.asset_id) '.
                      'JOIN '.TABLE_PREFIX.'portfolio AS P ON(T.portfolio_id = P.portfolio_id) ';
        if($portfolio_id !== 'ALL') {
            $sql .= 'WHERE T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        }  
        */

        $sql .= 'ORDER BY P.name , A.name ';
        $assets = $db->readSqlArray($sql);
        if($assets === 0) {
            $error = 'No Transactions exist for portfolio.';
            return false;
        }

        //NB:sometimes no trades booked directly to linked assets
        $sql = 'SELECT DISTINCT(T.asset_id_link),A.type_id,A.name,A.currency_id,P.name AS portfolio '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
                      'JOIN '.TABLE_PREFIX.'asset AS A ON(T.asset_id_link = A.asset_id) '.
                      'JOIN '.TABLE_PREFIX.'portfolio AS P ON(T.portfolio_id = P.portfolio_id) ';
        if($portfolio_id !== 'ALL') {
            $sql .= 'WHERE T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        } elseif($account_id !== 'ALL') {
            $sql .= 'JOIN '.TABLE_PREFIX.'account AS ACC ON(P.account_id = ACC.id) '.
                    'WHERE (P.account_id = "'.$db->escapeSql($account_id).'" OR FIND_IN_SET("'.$db->escapeSql($account_id).'",ACC.lineage) > 0) ';
        }              
        /*
        $sql = 'SELECT DISTINCT(T.asset_id_link),A.type_id,A.name,A.currency_id,P.name AS portfolio '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
                      'JOIN '.TABLE_PREFIX.'asset AS A ON(T.asset_id_link = A.asset_id) '.
                      'JOIN '.TABLE_PREFIX.'portfolio AS P ON(T.portfolio_id = P.portfolio_id) ';
        if($portfolio_id !== 'ALL') {
            $sql .= 'WHERE T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        } 
        */
        $assets_linked = $db->readSqlArray($sql);
        if($assets_linked !== 0) {
            //do NOT use array_merge() it will overwrite asset_id key!!
           foreach($assets_linked as $asset_id => $asset) {
              if(!isset($assets[$asset_id])) $assets[$asset_id] = $asset;
           }
        }

        return $assets;    
    } 


    public static function getMonthlySequence($from_month,$from_year,$to_month,$to_year)  {
        $months = [];
        
        //get all months and populate default empty price array
        $no_months = Date::getNoMonths($from_month,$from_year,$to_month,$to_year);
        $year = $from_year;
        $month = $from_month;

        for($n = 1; $n <= $no_months; $n++) {
            $months[$n] = ['mon'=>$month,'year'=>$year];

            $month++;
            if($month > 12) {
                $month = 1;
                $year = $year + 1;
            }
        }

        return $months;
    } 

    public static function getFirstAssetPrice($db,$asset_id,$date_cut) {
        $date = Date::getDate($date_cut);
        $months_cut = $date['year'] * 12 + $date['mon'];

        //check asset prices for first valid price before cutoff
        $sql = 'SELECT price FROM  '.TABLE_PREFIX.'price '.
               'WHERE asset_id = "'.$db->escapeSql($asset_id).'" AND '.
                     '(year * 12 + month) <= "'.$months_cut.'" '.
               'ORDER BY year DESC, month DESC LIMIT 1'; 
        $price = $db->readSqlValue($sql,0);

        //then check transactions for first valid price before cutoff
        if($price === 0) {
            $sql = 'SELECT price FROM  '.TABLE_PREFIX.'transact '.
                   'WHERE asset_id = "'.$db->escapeSql($asset_id).'" AND '.
                         'date <= "'.$date_cut.'" '.
                   'ORDER BY date DESC LIMIT 1'; 
            $price = $db->readSqlValue($sql,0);
        }

        //finally use first valid price 
        if($price === 0) {
            $sql = 'SELECT price FROM  '.TABLE_PREFIX.'price '.
                   'WHERE asset_id = "'.$db->escapeSql($asset_id).'" '.
                   'ORDER BY year, month LIMIT 1'; 
            $price = $db->readSqlValue($sql,0);
        }

        //echo 'asset id '.$asset_id.' = '.$price.'<br/>';

        return $price;
    }

    public static function getFirstForexRate($db,$currency_id_portfolio,$currency_id_asset,$date_cut) {
        $date = Date::getDate($date_cut);
        $months_cut = $date['year'] * 12 + $date['mon'];

        //first try standard currency pair
        $sql = 'SELECT rate FROM  '.TABLE_PREFIX.'forex '.
               'WHERE currency_id_portfolio = "'.$db->escapeSql($currency_id_portfolio).'" AND '.
                     'currency_id_transact = "'.$db->escapeSql($currency_id_asset).'" AND '.
                     '(year * 12 + month) <= "'.$months_cut.'" '.
               'ORDER BY year DESC, month DESC LIMIT 1'; 
        $rate = $db->readSqlValue($sql,0);

        //then invert currency pair 
        if($rate === 0) {
            $sql = 'SELECT rate FROM  '.TABLE_PREFIX.'forex '.
                   'WHERE currency_id_portfolio = "'.$db->escapeSql($currency_id_asset).'" AND '.
                         'currency_id_transact = "'.$db->escapeSql($currency_id_portfolio).'" AND '.
                         '(year * 12 + month) <= "'.$months_cut.'" '.
                   'ORDER BY year DESC, month DESC LIMIT 1'; 
            $rate = $db->readSqlValue($sql,0);
            if($rate !== 0) $rate = 1 / $rate;
        }

        //then try first valid standard currency pair
        if($rate === 0) {
            $sql = 'SELECT rate FROM  '.TABLE_PREFIX.'forex '.
                   'WHERE currency_id_portfolio = "'.$db->escapeSql($currency_id_portfolio).'" AND '.
                         'currency_id_transact = "'.$db->escapeSql($currency_id_asset).'" '.
                   'ORDER BY year, month LIMIT 1'; 
            $rate = $db->readSqlValue($sql,0);
        }

        //finally try first valid inverted currency pair
        if($rate === 0) {
            $sql = 'SELECT rate FROM  '.TABLE_PREFIX.'forex '.
                   'WHERE currency_id_portfolio = "'.$db->escapeSql($currency_id_asset).'" AND '.
                         'currency_id_transact = "'.$db->escapeSql($currency_id_portfolio).'" '.
                   'ORDER BY year, month LIMIT 1'; 
            $rate = $db->readSqlValue($sql,0);
            if($rate !== 0) $rate = 1 / $rate;
        }
            
        return $rate;
    }   

    public static function setupMonthlyValuation($db,$currency_id,$assets = [],$date_from,$date_to)  {
        $output = [];
        $monthly_prices = [];
        $monthly_forex = [];
        $price_empty = [];
        $price_unity = [];
        
        $from = Date::getDate($date_from);
        $to = Date::getDate($date_to);

        $from_month_count = ($from['year'] * 12) + $from['mon']; 
        $to_month_count = ($to['year'] * 12) + $to['mon'];

        //get performance months and initialise empty and unity(=1) price arrays
        $months = self::getMonthlySequence($from['mon'],$from['year'],$to['mon'],$to['year']);
        foreach($months as $month) {
            $key = $month['year'].':'.$month['mon'];
            $price_empty[$key] = 0;
            $price_unity[$key] = 1;
        }

        
        $asset_currencies = [];

        //assign monthly price data to traded assets
        foreach($assets as $asset_id => $asset) {
            //populate currency array with all asset currencies
            if(!in_array($asset['currency_id'],$asset_currencies)) {
                $asset_currencies[] = $asset['currency_id'];
            }

            if($asset['type_id'] === 'CASH') {
                $price_assign = $price_unity;
            } else {    
                $price_assign = $price_empty;
                
                //get first valid price before from date
                $first_price = self::getFirstAssetPrice($db,$asset_id,$date_from);

                $sql = 'SELECT year,month,price '.
                       'FROM  '.TABLE_PREFIX.'price '.
                       'WHERE asset_id = "'.$db->escapeSql($asset_id).'" AND '.
                             '(year * 12 + month) >= "'.$from_month_count.'" AND '.
                             '(year * 12 + month) <= "'.$to_month_count.'" '.
                       'ORDER BY year, month'; 
                $first_col_key = false;
                $asset_prices = $db->readSqlArray($sql,$first_col_key);
                if($asset_prices !== 0) {
                    foreach($asset_prices as $price) {
                        $key = $price['year'].':'.$price['month'];
                        $price_assign[$key] = $price['price'];
                    }
                } 

                //space fill prices with first valid price or previous value where set
                $price_prev = $first_price;
                foreach($months as $month) {
                    $key = $month['year'].':'.$month['mon'];
                    if($price_assign[$key] === 0 and $price_prev !== 0) $price_assign[$key] = $price_prev;

                    $price_prev = $price_assign[$key];
                }
            }    

            $monthly_prices[$asset_id] = $price_assign;
        }

        //assign monthly forex rates between assets and reporting currency id
        foreach($asset_currencies as $asset_currency_id) {
            if($asset_currency_id === $currency_id) {
                $forex_assign = $price_unity;
            } else {
                $forex_assign = $price_empty;
                
                //get first valid rate before from date
                $first_rate = self::getFirstForexRate($db,$currency_id,$asset_currency_id,$date_from);
                $rate_count = 0;

                $sql = 'SELECT year,month,rate '.
                       'FROM  '.TABLE_PREFIX.'forex '.
                       'WHERE currency_id_portfolio = "'.$db->escapeSql($currency_id).'" AND '.
                             'currency_id_transact = "'.$db->escapeSql($asset_currency_id).'" AND '.
                             '(year * 12 + month) >= "'.$from_month_count.'" AND '.
                             '(year * 12 + month) <= "'.$to_month_count.'" '.
                       'ORDER BY year, month'; 
                $first_col_key = false;
                $fx_rates = $db->readSqlArray($sql,$first_col_key);
                if($fx_rates !== 0) {
                    foreach($fx_rates as $rate) {
                        $rate_count++;
                        $key = $rate['year'].':'.$rate['month'];
                        $forex_assign[$key] = $rate['rate'];
                    }
                } 

                //use inverse rate data if standard rates not complete
                
                if($rate_count !== count($months)) {
                    $sql = 'SELECT year,month,rate '.
                           'FROM  '.TABLE_PREFIX.'forex '.
                           'WHERE currency_id_portfolio = "'.$db->escapeSql($asset_currency_id).'" AND '.
                                 'currency_id_transact = "'.$db->escapeSql($currency_id).'" AND '.
                                 '(year * 12 + month) >= "'.$from_month_count.'" AND '.
                                 '(year * 12 + month) <= "'.$to_month_count.'" '.
                           'ORDER BY year, month'; 
                    $fx_rates = $db->readSqlArray($sql,$first_col_key);
                    if($fx_rates !== 0) {
                        foreach($fx_rates as $rate) {
                            $key = $rate['year'].':'.$rate['month'];
                            //only assign missing rates
                            if($forex_assign[$key] === 0) $forex_assign[$key] = 1 / $rate['rate'];
                        }
                    }
                }    
                

                //space fill rates with first valid rate or previous value where set
                $rate_prev = $first_rate;
                foreach($months as $month) {
                    $key = $month['year'].':'.$month['mon'];
                    if($forex_assign[$key] === 0 and $rate_prev !== 0) $forex_assign[$key] = $rate_prev;

                    $rate_prev = $forex_assign[$key];
                }
            }

            $monthly_forex[$asset_currency_id] = $forex_assign;    
        }    


        $output['months'] = $months;
        $output['prices'] = $monthly_prices;
        $output['forex']  = $monthly_forex;

        return $output;
    }      

    //gets initial nominal trade and cash balances using all transactions BEFORE balance date
    public static function getNominalBalances($db,$account_id,$portfolio_id,$assets = [],$date_balance)  {
        $balances = [];
        
        //initialise balances for all requested assets
        //cash asset balances mmust be included event though always zero 
        foreach($assets as $asset_id => $asset) {
           $balances[$asset_id] = 0; 
        }

        //setup sql fragments
        $sql_join = '';
        $sql_where = 'WHERE T.date < "'.$db->escapeSql($date_balance).'"  ';

        if($portfolio_id !== 'ALL') {
            $sql_where .= 'AND T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';           
        } elseif($account_id !== 'ALL') {
            $sql_join .= 'JOIN '.TABLE_PREFIX.'portfolio AS P ON(T.portfolio_id = P.portfolio_id) '.
                         'JOIN '.TABLE_PREFIX.'account AS ACC ON(P.account_id = ACC.id) '; 

            $sql_where .= 'AND (P.account_id = "'.$db->escapeSql($account_id).'" OR FIND_IN_SET("'.$db->escapeSql($account_id).'",ACC.lineage) > 0) ';         
        }
        //final sql statement
        $sql = 'SELECT T.asset_id,T.asset_id_link,T.type_id,SUM(T.nominal) AS net_nominal,SUM(T.amount) AS net_amount '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
               $sql_join.$sql_where.
               'GROUP BY T.asset_id,T.asset_id_link,T.type_id ';
               

        /*    
        //could do all in query with if statements if speed ever an issue
        $sql = 'SELECT T.asset_id,T.asset_id_link,T.type_id,SUM(T.nominal) AS net_nominal,SUM(T.amount) AS net_amount '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
               'WHERE T.date < "'.$db->escapeSql($date_balance).'"  ';
        if($portfolio_id !== 'ALL') {
            $sql .= 'AND T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        } 
        $sql .= 'GROUP BY T.asset_id,T.asset_id_link,T.type_id ';
        */

        $first_col_key = false;
        $transact_nominals = $db->readSqlArray($sql,$first_col_key);
        if($transact_nominals !== 0) {
            foreach($transact_nominals as $nominal) {
                switch($nominal['type_id']) {
                    //trades
                    case 'BUY':  $balances[$nominal['asset_id']] += abs($nominal['net_nominal']); 
                                 $balances[$nominal['asset_id_link']] -= abs($nominal['net_amount']); 
                                 break;
                    case 'SELL': $balances[$nominal['asset_id']] -= abs($nominal['net_nominal']);
                                 $balances[$nominal['asset_id_link']] += abs($nominal['net_amount']);
                                 break;
                    case 'ADJUST': $balances[$nominal['asset_id']] += $nominal['net_nominal']; break;

                    //cashflows
                    case 'INCOME': $balances[$nominal['asset_id']] += abs($nominal['net_nominal']); break;
                    case 'ADD': $balances[$nominal['asset_id']] += abs($nominal['net_nominal']); break;
                    case 'EXPENSE': $balances[$nominal['asset_id']] -= abs($nominal['net_nominal']); break;
                    case 'WITHDRAW': $balances[$nominal['asset_id']] -= abs($nominal['net_nominal']); break;
                }
            }
        }

        return $balances;
    }

    public static function performanceReport($db,$account_id,$portfolio_id,$currency_id,$from_month,$from_year,$to_month,$to_year,$options = [],&$error)  {
        $error = '';
        $error_tmp = '';
        $html = '';
        
        if(!isset($options['output'])) $options['output'] = 'HTML';
        if(!isset($options['return_method'])) $options['return_method'] = 'START';

        self::validateMonthInterval($from_month,$from_year,$to_month,$to_year,$error_tmp);
        if($error_tmp !== '') $error .= $error_tmp;

        //get all assets with transactions
        $assets = self::getPortfolioAssets($db,$account_id,$portfolio_id,$error_tmp);
        if($error_tmp !== '') $error .= $error_tmp;

        if($error !== '') return false;

        //dates from first day of start month to last day of end month
        $date_from = date('Y-m-d',mktime(0,0,0,$from_month,1,$from_year));
        $date_to = date('Y-m-d',mktime(0,0,0,$to_month+1,0,$to_year));

        if($currency_id === 'DEFAULT') {
            die('WTF');
            $portfolio = self::getPortfolio($db,$portfolio_id);
            $currency_id = $portfolio['currency_id'];    
        }
        

        //need prices from previous month for initial valuation
        $from = Date::getDate($date_from);
        $date_from_prices = date('Y-m-d',mktime(0,0,0,$from['mon']-1,1,$from['year']));
        
        //returns ['months'] and ['prices'] data
        $valuation = self::setupMonthlyValuation($db,$currency_id,$assets,$date_from_prices,$date_to);
        $performance_months = $valuation['months'];
        $asset_prices = $valuation['prices'];
        $asset_forex = $valuation['forex'];
        
        //get previous month details and shift/remove from performance_months 
        $initial_balance_month = array_shift($performance_months);
        
        //get initial asset nominal balances from all transactions since inception up to start date
        $balances = self::getNominalBalances($db,$account_id,$portfolio_id,$assets,$date_from);
        $month_start_value=0;
        $month_key = $initial_balance_month['year'].':'.$initial_balance_month['mon'];
        foreach($assets as $asset_id => $asset) {
            $asset_value = $balances[$asset_id] * $asset_prices[$asset_id][$month_key] * $asset_forex[$asset['currency_id']][$month_key];
            //echo $asset['name'].':'.$asset_prices[$asset_id][$month_key].'<br/>';
            $month_start_value += $asset_value; 
        }
       
        //balances modified as step through performance months
        $performance = [];

        
        //setup sql fragments
        $sql_join = 'JOIN '.TABLE_PREFIX.'asset as A ON(T.asset_id = A.asset_id) ';
        $sql_where = 'WHERE T.date >= "'.$db->escapeSql($date_from).'" AND '.
                           'T.date <= "'.$db->escapeSql($date_to).'"  ';

        if($portfolio_id !== 'ALL') {
            $sql_where .= 'AND T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';           
        } elseif($account_id !== 'ALL') {
            $sql_join .= 'JOIN '.TABLE_PREFIX.'portfolio AS P ON(T.portfolio_id = P.portfolio_id) '.
                         'JOIN '.TABLE_PREFIX.'account AS ACC ON(P.account_id = ACC.id) '; 

            $sql_where .= 'AND (P.account_id = "'.$db->escapeSql($account_id).'" OR FIND_IN_SET("'.$db->escapeSql($account_id).'",ACC.lineage) > 0) ';         
        }
        //final sql statement
        $sql = 'SELECT T.transact_id,CONCAT(YEAR(T.date),":",MONTH(T.date)) AS month_key,T.date,T.type_id,'.
                      'T.asset_id,T.asset_id_link,A.currency_id,T.nominal,T.price,T.amount '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
               $sql_join.$sql_where.
               'ORDER BY T.date ';
                      

        /*       
        $sql = 'SELECT T.transact_id,CONCAT(YEAR(T.date),":",MONTH(T.date)) AS month_key,T.date,T.type_id,'.
                      'T.asset_id,T.asset_id_link,A.currency_id,T.nominal,T.price,T.amount '.
               'FROM  '.TABLE_PREFIX.'transact AS T JOIN '.TABLE_PREFIX.'asset as A ON(T.asset_id = A.asset_id) '.
               'WHERE T.date >= "'.$db->escapeSql($date_from).'" AND '.
                     'T.date <= "'.$db->escapeSql($date_to).'"  ';
        if($portfolio_id !== 'ALL') {
            $sql .= 'AND T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        }             
        $sql .= 'ORDER BY T.date ';
        */

        //finally process all transactions over report period
        $transactions = $db->readSqlArray($sql);           
        
        foreach($performance_months as $month) {
            $month_key = $month['year'].':'.$month['mon'];

            $period = ['name' => $month_key,
                       'start_value' => $month_start_value,
                       'add' => 0,
                       'withdraw' => 0,
                       'buy' => 0,
                       'sell' => 0,
                       'income' => 0,
                       'expense' => 0,
                       'end_value' => 0];
            
            if($transactions !== 0) {
                foreach($transactions as $transact_id => $transact) {
                    if($transact['month_key'] === $month_key) {
                        //self::processPeriodTransaction($transact)

                        if($transact['type_id'] === 'BUY') {
                            $period['buy'] += $transact['amount'] * $asset_forex[$transact['currency_id']][$month_key];
                            $balances[$transact['asset_id']] += abs($transact['nominal']);
                            //linked asset always a CASH trading account 
                            $balances[$transact['asset_id_link']] -= abs($transact['amount']);
                        }

                        if($transact['type_id'] === 'SELL') {
                            $period['sell'] += $transact['amount'] * $asset_forex[$transact['currency_id']][$month_key];
                            $balances[$transact['asset_id']] -= abs($transact['nominal']);
                            //linked asset always a CASH trading account 
                            $balances[$transact['asset_id_link']] += abs($transact['amount']);
                        }

                        if($transact['type_id'] === 'ADJUST') {
                            $balances[$transact['asset_id']] += $transact['nominal'];
                        }

                        if($transact['type_id'] === 'INCOME') {
                            $balances[$transact['asset_id']] += abs($transact['nominal']);
                            $period['income'] += $transact['nominal'] * $asset_forex[$transact['currency_id']][$month_key];
                        } 

                        if($transact['type_id'] === 'EXPENSE') {
                            $balances[$transact['asset_id']] -= abs($transact['nominal']);
                            $period['expense'] += $transact['nominal'] * $asset_forex[$transact['currency_id']][$month_key];
                        } 

                        if($transact['type_id'] === 'ADD') {
                            $balances[$transact['asset_id']] += abs($transact['nominal']);
                            $period['add'] += $transact['nominal'] * $asset_forex[$transact['currency_id']][$month_key];
                        }

                        if($transact['type_id'] === 'WITHDRAW') {
                            $balances[$transact['asset_id']] -= abs($transact['nominal']);
                            $period['withdraw'] += $transact['nominal'] * $asset_forex[$transact['currency_id']][$month_key];
                        }    
                    }
                }
            }

            //get end of month value for all assets
            foreach($assets as $asset_id => $asset) {
                $asset_value = ($balances[$asset_id] * $asset_prices[$asset_id][$month_key]) * $asset_forex[$asset['currency_id']][$month_key];
                $period['end_value'] += $asset_value; 
            }
         
            //calculate period return and other values assuming external cashflows at START
            $period['return'] = self::calcPeriodReturn($options['return_method'],$period);

            $performance[$month_key] = $period;

            $month_start_value = $period['end_value'];
        } 

        //only return stuff
        if($error === '') {
            if($options['output'] === 'HTML') {
                $html_options['col_type'] = ['start_value'=>'CASH2','add'=>'CASH2','withdraw'=>'CASH2','buy'=>'CASH2','sell'=>'CASH2','income'=>'CASH2','expense'=>'CASH2','end_value'=>'CASH2','return'=>'PCT2'];
                $html = Html::arrayDumpHtml($performance,$html_options);
                return $html;
            }

            if($options['output'] === 'DATA') {
                return $performance;
            }
        }    
        

        return false;     
    }

    public static function assetBalancesReport($db,$account_id,$portfolio_id,$currency_id,$from_month,$from_year,$to_month,$to_year,$options = [],&$error)  {
        $error = '';
        $error_tmp = '';
        $html = '';
        $output = [];
        
        if(!isset($options['output'])) $options['output'] = 'HTML';

        self::validateMonthInterval($from_month,$from_year,$to_month,$to_year,$error_tmp);
        if($error_tmp !== '') $error .= $error_tmp;

        //get all assets with transactions
        $assets = self::getPortfolioAssets($db,$account_id,$portfolio_id,$error_tmp);
        if($error_tmp !== '') $error .= $error_tmp;

        if($error !== '') return false;

        //dates from first day of start month to last day of end month
        $date_from = date('Y-m-d',mktime(0,0,0,$from_month,1,$from_year));
        $date_to = date('Y-m-d',mktime(0,0,0,$to_month+1,0,$to_year));

        if($currency_id === 'DEFAULT') {
            die('WTF');
            $portfolio = self::getPortfolio($db,$portfolio_id);
            $currency_id = $portfolio['currency_id'];    
        }
        
        //need prices from previous month for initial valuation
        $from = Date::getDate($date_from);
        $date_from_prices = date('Y-m-d',mktime(0,0,0,$from['mon']-1,1,$from['year']));
        
        //returns ['months'] and ['prices'] data
        $valuation = self::setupMonthlyValuation($db,$currency_id,$assets,$date_from_prices,$date_to);
        $balance_months = $valuation['months'];
        $asset_prices = $valuation['prices'];
        $asset_forex = $valuation['forex'];

        //get previous month details and shift/remove from balance_months 
        $initial_balance_month = array_shift($balance_months);
        
        //get initial asset nominal balances from all transactions since inception up to start date
        //all months will include data for all assets that occur over entire period
        $balances = self::getNominalBalances($db,$account_id,$portfolio_id,$assets,$date_from);
        $month_key = $initial_balance_month['year'].':'.$initial_balance_month['mon'];
        
        $period = [];
        foreach($assets as $asset_id => $asset) {
            $period[$asset_id]['name'] = $asset['name'];
            $period[$asset_id]['start_units'] = $balances[$asset_id];
            $period[$asset_id]['start_price'] = round(($asset_prices[$asset_id][$month_key] * $asset_forex[$asset['currency_id']][$month_key]),2);
            $period[$asset_id]['start_value'] = round(($balances[$asset_id] * $asset_prices[$asset_id][$month_key] * $asset_forex[$asset['currency_id']][$month_key]),2);
        }
       
        //NB: IDENTICAL SQL CODE IN PERFORMANCE REPORT, NEED TO CONSOLIDATE
        //setup sql fragments
        $sql_join = 'JOIN '.TABLE_PREFIX.'asset as A ON(T.asset_id = A.asset_id) ';
        $sql_where = 'WHERE T.date >= "'.$db->escapeSql($date_from).'" AND '.
                           'T.date <= "'.$db->escapeSql($date_to).'"  ';

        if($portfolio_id !== 'ALL') {
            $sql_where .= 'AND T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';           
        } elseif($account_id !== 'ALL') {
            $sql_join .= 'JOIN '.TABLE_PREFIX.'portfolio AS P ON(T.portfolio_id = P.portfolio_id) '.
                         'JOIN '.TABLE_PREFIX.'account AS ACC ON(P.account_id = ACC.id) '; 

            $sql_where .= 'AND (P.account_id = "'.$db->escapeSql($account_id).'" OR FIND_IN_SET("'.$db->escapeSql($account_id).'",ACC.lineage) > 0) ';         
        }
        //final sql statement
        $sql = 'SELECT T.transact_id,CONCAT(YEAR(T.date),":",MONTH(T.date)) AS month_key,T.date,T.type_id,'.
                      'T.asset_id,T.asset_id_link,A.currency_id,T.nominal,T.price,T.amount '.
               'FROM  '.TABLE_PREFIX.'transact AS T '.
               $sql_join.$sql_where.
               'ORDER BY T.date ';

        /*       
        $sql = 'SELECT T.transact_id,CONCAT(YEAR(T.date),":",MONTH(T.date)) AS month_key,T.date,T.type_id,'.
                      'T.asset_id,T.asset_id_link,A.currency_id,T.nominal,T.price,T.amount '.
               'FROM  '.TABLE_PREFIX.'transact AS T JOIN '.TABLE_PREFIX.'asset as A ON(T.asset_id = A.asset_id) '.
               'WHERE T.date >= "'.$db->escapeSql($date_from).'" AND '.
                     'T.date <= "'.$db->escapeSql($date_to).'"  ';
        if($portfolio_id !== 'ALL') {
            $sql .= 'AND T.portfolio_id = "'.$db->escapeSql($portfolio_id).'" ';
        } 
        $sql .= 'ORDER BY T.date ';
        */

        //process all transactions over report period
        $transactions = $db->readSqlArray($sql);           
        
        foreach($balance_months as $month) {
            $month_key = $month['year'].':'.$month['mon'];

            if($transactions !== 0) {
                foreach($transactions as $transact_id => $transact) {
                    if($transact['month_key'] === $month_key) {
                        if($transact['type_id'] === 'BUY') {
                            $balances[$transact['asset_id']] += abs($transact['nominal']);
                            $balances[$transact['asset_id_link']] -= abs($transact['amount']);
                        }

                        if($transact['type_id'] === 'SELL') {
                            $balances[$transact['asset_id']] -= abs($transact['nominal']);
                            $balances[$transact['asset_id_link']] += abs($transact['amount']);
                        }

                        if($transact['type_id'] === 'ADJUST') {
                            $balances[$transact['asset_id']] += $transact['nominal'];
                        }

                        if($transact['type_id'] === 'INCOME') {
                            $balances[$transact['asset_id']] += abs($transact['nominal']);
                        } 

                        if($transact['type_id'] === 'EXPENSE') {
                            $balances[$transact['asset_id']] -= abs($transact['nominal']);
                        } 

                        if($transact['type_id'] === 'ADD') {
                            $balances[$transact['asset_id']] += abs($transact['nominal']);
                        }

                        if($transact['type_id'] === 'WITHDRAW') {
                            $balances[$transact['asset_id']] -= abs($transact['nominal']);
                        }    
                    }
                }
            }


            foreach($assets as $asset_id => $asset) {
                $period[$asset_id]['change_units'] = 0;
                //$period[$asset_id]['change_value'] = 0;

                $period[$asset_id]['end_units'] = $balances[$asset_id];
                $period[$asset_id]['end_price'] = round(($asset_prices[$asset_id][$month_key] * $asset_forex[$asset['currency_id']][$month_key]),2);
                $period[$asset_id]['end_value'] = round(($balances[$asset_id] * $asset_prices[$asset_id][$month_key] * $asset_forex[$asset['currency_id']][$month_key]),2);
        
                $period[$asset_id]['change_units'] = $period[$asset_id]['end_units'] - $period[$asset_id]['start_units'];
                //$period[$asset_id]['change_value'] = $period[$asset_id]['end_value'] - $period[$asset_id]['start_value'];
            }   
            
            if($options['output'] === 'HTML') {
                $html_options['col_type'] = ['start_units'=>'R','start_price'=>'DBL2','start_value'=>'CASH2','change_units'=>'R','end_price'=>'DBL2','end_units'=>'R','end_value'=>'CASH2'];
                $html .= '<h1>'.$month_key.'</h1>'.Html::arrayDumpHtml($period,$html_options);
            }

            if($options['output'] === 'DATA') {
                $output[$month_key] = $period;
            }    
            
            //for next iteraion
            foreach($assets as $asset_id => $asset) {
                $period[$asset_id]['start_units'] = $period[$asset_id]['end_units'];
                $period[$asset_id]['start_price'] = $period[$asset_id]['end_price'];
                $period[$asset_id]['start_value'] = $period[$asset_id]['end_value'];
            }    
        } 

        //only return stuff
        if($error === '') {
            if($options['output'] === 'HTML') {
                return $html;
            }

            if($options['output'] === 'DATA') {
                return $output;
            }
        }    
        

        return false;   
    }

    public static function calcPeriodReturn($method,$period = []) {
        $return = 0;

        $gain = $period['end_value'] - ($period['start_value'] + $period['add'] - $period['withdraw']);

        //assumes all external cashflows happen at start of period
        if($method === 'START') {
            $base =  $period['start_value'] + ($period['add'] - $period['withdraw']);
        }
        
        //assumes all external cashflows happen at end of period
        if($method === 'END') {
            $base =  $period['start_value'];
        } 

        //assumes external cashflows occur in middle of period
        if($method === 'DIETZ') {
            $base =  $period['start_value'] + ($period['add'] - $period['withdraw'])/2;
        } 

        $base = abs($base);
        if($base > 0.001 ) {
            $return = ($gain / $base) * 100; 
        }
        
        $return = round($return,5);

        return $return;    
    }

    public static function checkTransactionValid($db,$type,$portfolio_id,$data,&$error)  {
        $error = '';
        $error_tmp = '';
        //assume $data allready has basic validation done like date/number formats

        $check_linked_currency = false;

        $asset = self::getAsset($db,$data['asset_id']);

        self::checkTransactionPeriod($db,$portfolio_id,$data['date'],$error_tmp);
        if($error_tmp != '') $error .= $error_tmp;

        if($type === 'CASH') {
            if($data['type_id'] === 'INCOME' or $data['type_id'] === 'EXPENSE') {
                if($data['asset_id_link'] == 0) {
                    $error .= 'Income or Expense cashflows must be linked to an asset.';
                } else {
                    $check_linked_currency = true;
                }
            }
        }

        if($type === 'TRADE') {
            //check total amount matches nominal X price
            $amount = $data['nominal'] * $data['price'];
            if(abs(1 - $amount / $data['amount']) > 0.01) {
                $error .= 'Invalid values: '.$data['nominal'].' X '.$data['price'].' NOT = '.$data['amount'];
            }

            if($data['type_id'] === 'ADJUST') {
                //adjustments have NO counterparty
                if($data['asset_id_link'] != 0) $error .= 'Adjustments do not have a linked asset.';;
            } 

            if($data['type_id'] === 'BUY' or $data['type_id'] === 'SELL') {
                $check_linked_currency = true;

                if($data['asset_id'] === $data['asset_id_link']) $error .= 'Linked Asset Cannot be same as trade asset.';
            }
        }

        if($check_linked_currency) {
            $linked_asset = self::getAsset($db,$data['asset_id_link']);
            if($asset['currency_id'] !== $linked_asset['currency_id']) {
               $error .= 'Asset['.$asset['name'].'] currency['.$asset['currency_id'].'] must be same as '.
                         'linked asset['.$linked_asset['name'].'] currency['.$linked_asset['currency_id'].'].'; 
            }
        }
    }


    public static function checkTransactionPeriod($db,$portfolio_id,$date,&$error)  {
        $error = '';
        
        $date = $db->escapeSql($date);
        
        $sql = 'SELECT name,status,date_start,date_end '.
               'FROM '.TABLE_PREFIX.'period '.
               'WHERE portfolio_id = "'.$db->escapeSql($portfolio_id).'" AND '.
                     'date_start <= "'.$date.'" AND date_end >= "'.$date.'" LIMIT 1 ';
        $period = $db->readSqlRecord($sql);           
        if($period !== 0) {
            if($period['status'] === 'CLOSED') {
                $error .= 'Period['.$period['name'].'] is CLOSED. You cannot add or modify any data for this period.';
            }    
        } 
        
        if($error === '') return true; else return false;     
    }  
    
}