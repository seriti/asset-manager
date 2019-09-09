<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;
use App\Asset\Currency;

class CurrencyController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'currency'; 
        $table = new Currency($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'ALL Currencies';
         
        return $this->container->view->render($response,'admin.php',$template);
    }
}