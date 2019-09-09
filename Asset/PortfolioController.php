<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;
use App\Asset\Portfolio;

class PortfolioController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'portfolio'; 
        $table = new Portfolio($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Portfolios';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}