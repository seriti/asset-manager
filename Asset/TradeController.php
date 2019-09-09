<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;

use App\Asset\Trade;
use App\Asset\TABLE_PREFIX;
use App\Asset\MODULE_LOGO;
use App\Asset\COMPANY_NAME;

class TradeController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'transact'; 
        $table = new Trade($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Trades';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}