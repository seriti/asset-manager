<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;
use App\Asset\AssetPrice;

class AssetPriceController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table_name = TABLE_PREFIX.'price'; 
        $table = new AssetPrice($this->container->mysql,$this->container,$table_name);

        $table->setup();
        $html = $table->processTable();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Asset prices';
        
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}