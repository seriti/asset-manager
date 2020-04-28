<?php
namespace App\Asset;

use App\Asset\Account;
use Psr\Container\ContainerInterface;

class AccountController
{
    protected $container;
    

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function __invoke($request, $response, $args)
    {
        
        
        if($this->container->user->getAccessLevel() !== 'GOD') {
            $template['html'] = '<h1>Insufficient access rights!</h1>';
        } else {  
                    
            $table = TABLE_PREFIX.'account';

            $tree = new Account($this->container->mysql,$this->container,$table);

            $param = ['row_name'=>'Account','col_label'=>'title'];
            $tree->setup($param);
            $html = $tree->processTree();
            
            $template['html'] = $html;
            $template['title'] = MODULE_LOGO.'Portfolio account hierarchy';
            
            //$template['javascript'] = $tree->getJavascript();
        }    
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}