<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;

use Seriti\Tools\Secure;

use App\Asset\Helpers;


class Ajax
{
    protected $container;
    protected $db;
    protected $user;

    protected $debug = false;
    //Class accessed outside /App/Auction so cannot use TABLE_PREFIX constant
    protected $table_prefix = '';
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->db = $this->container->mysql;
        $this->user = $this->container->user;

        //Class accessed outside /App/Auction so cannot use TABLE_PREFIX constant
        $module = $this->container->config->get('module','asset');
        $this->table_prefix = $module['table_prefix'];

        if(defined('\Seriti\Tools\DEBUG')) $this->debug = \Seriti\Tools\DEBUG;
    }


    public function __invoke($request, $response, $args)
    {
        $mode = '';
        $output = '';

        if(isset($_GET['mode'])) $mode = Secure::clean('basic',$_GET['mode']);

        /*
        $this->csrf_token = Secure::clean('basic',Form::getVariable('csrf_token','GP'));

        $this->user_access_level = $this->getContainer('user')->getAccessLevel();
        $this->user_id = $this->getContainer('user')->getId();
        $this->user_csrf_token = $this->getContainer('user')->getCsrfToken();

        $this->verifyCsrfToken($error); //maybe need a verifyPublicCsrfToken() with more meaningful error message??
        */

        if($mode === 'account_portfolios') $output = $this->getAccountPortfolios($_POST);

        return $output;
    }

    protected function getAccountPortfolios($form)
    {
        $error = '';
        
        /*
        $output = 'Hello you beauty:';

        foreach($form as $id => $value) {
            $output .= $id.':'.$value."\r\n";            
        }
        return $output;
        */

        $account_id = Secure::clean('alpha',$form['account_id']);
        if($account_id === 'ALL') {
            $sql = 'SELECT portfolio_id,name FROM '.$this->table_prefix.'portfolio '.
                   'WHERE status = "OK" '.
                   'ORDER BY name';
            $portfolios = $this->db->readSqlList($sql);
            $account['title'] = ''; 
        } else {
            $account_id = $this->db->escapeSql($account_id);
             
            //get account details and lineage
            $sql = 'SELECT id,title,currency_id '.
                   'FROM '.$this->table_prefix.'account WHERE id = "'.$account_id.'" ';
            $account = $this->db->readSqlRecord($sql);
                       
            //get all portfolios belonging to account OR sub-accounts
            //NB: A.lineage is in csv format already
            $sql = 'SELECT P.portfolio_id,P.name '.
                   'FROM '.$this->table_prefix.'portfolio AS P '.
                   'JOIN '.$this->table_prefix.'account AS A ON(P.account_id = A.id)'.
                   'WHERE (P.account_id = "'.$account_id.'" OR FIND_IN_SET("'.$account_id.'",A.lineage) > 0) '.
                   'ORDER BY P.name';
            $portfolios = $this->db->readSqlList($sql);    
        }
              

        if($portfolios == 0) {
            $output = 'ERROR: NO '.$account['title'].' portfolios found';
        } else {
            $portfolios = ['ALL'=>'All '.$account['title'].' portfolios'] + $portfolios;
            $output = json_encode($portfolios); 
        }



        return $output;

    }
}