<?php
namespace App\Asset;

use Psr\Container\ContainerInterface;
use App\Asset\Task;

class TaskController
{
    protected $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($request, $response, $args)
    {
        $param = [];
        $task = new Task($this->container->mysql,$this->container,$param);

        $task->setup();
        $html = $task->processTasks();
        
        $template['html'] = $html;
        $template['title'] = MODULE_LOGO.'Portfolio Tasks';
        
        return $this->container->view->render($response,'admin.php',$template);
    }
}