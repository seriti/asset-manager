<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;
use App\Asset\Period;

class PeriodController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'period'; 
        $table = new Period($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All reporting periods';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}