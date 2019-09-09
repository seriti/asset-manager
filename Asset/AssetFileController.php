<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;
use App\Asset\AssetFile;

class AssetFileController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $table = TABLE_PREFIX.'file'; 
        $upload = new AssetFile($this->container->mysql,$this->container,$table);

        $upload->setup();
        $html = $upload->processUpload();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Asset documents';
        
        return $this->container->view->render($response,'admin_popup.php',$template);
    }
}