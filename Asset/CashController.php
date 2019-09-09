<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;

use App\Asset\Cash;
use App\Asset\TABLE_PREFIX;
use App\Asset\MODULE_LOGO;
use App\Asset\COMPANY_NAME;

class CashController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'transact'; 
        $table = new Cash($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Cashflows';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}