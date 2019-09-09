<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;
use App\Asset\Asset;

class AssetController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'asset'; 
        $table = new Asset($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'All Assets';
         
        return $this->container->view->render($response,'admin.php',$template);
    }
}