<?php 
namespace App\Asset;

use Psr\Container\ContainerInterface;
use Seriti\Tools\BASE_URL;
use Seriti\Tools\SITE_NAME;
use Seriti\Tools\CURRENCY_ID;

class Config
{
    
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

    }

    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke($request, $response, $next)
    {
        
        $module = $this->container->config->get('module','asset');
        //$ledger = $this->container->config->get('module','ledger');
        $menu = $this->container->menu;
        $cache = $this->container->cache;
        $db = $this->container->mysql;

        $user_specific = true;
        $cache->setCache('Assets',$user_specific);
        //$cache->eraseAll();
        
        define('TABLE_PREFIX',$module['table_prefix']);
        define('MODULE_ID','ASSET');
        define('MODULE_LOGO','<img src="'.BASE_URL.'images/assets48.png"> ');
        define('MODULE_PAGE',URL_CLEAN_LAST);

        define('ASSET_TYPE',['EXCHANGE'=>'Exchange traded instrument',
                             'FUND'=>'Non-exchange traded fund',
                             'CASH'=>'Bank/Moneymarket/Trading account',
                             'FIXED'=>'Fixed asset',
                             'MOVEABLE'=>'Moveable asset']);

        //NB: trades and cashflows stored in same transaction table so type must be unique
        define('TRADE_TYPE',['BUY'=>'Buy an asset',
                             'SELL'=>'Sell an asset',
                             'ADJUST'=>'Adjust asset holding']);

        define('CASHFLOW_TYPE',['INCOME'=>'Asset income',
                                'EXPENSE'=>'Asset expense',
                                'ADD'=>'Deposit cash into asset',
                                'WITHDRAW'=>'Withdraw cash from asset']);

        define('MONTH_LIST',[1=>'January',
                             2=>'February',
                             3=>'March',
                             4=>'April',
                             5=>'May', 
                             6=>'June', 
                             7=>'July',
                             8=>'August',
                             9=>'September',
                             10=>'October',
                             11=>'November',
                             12=>'December']);
        
        $system = []; //can specify any GOD access system menu items
        $options['logo_link'] = BASE_URL.'admin/dashboard';
        $options['active_link'] = 'admin/asset/'.MODULE_PAGE;
        $menu_html = $menu->buildMenu($system,$options);
        $this->container->view->addAttribute('menu',$menu_html);

        //define('MODULE_NAV',$menu->buildNav($module['route_list'],MODULE_PAGE));
        $submenu_html = $menu->buildNav($module['route_list'],MODULE_PAGE);
        $this->container->view->addAttribute('sub_menu',$submenu_html);
       
        $response = $next($request, $response);
        
        return $response;
    }
}