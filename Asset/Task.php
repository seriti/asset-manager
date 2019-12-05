<?php 
namespace App\Asset;

use App\Asset\Helpers;

use Seriti\Tools\CURRENCY_ID;
use Seriti\Tools\Form;
use Seriti\Tools\Task as SeritiTask;

class Task extends SeritiTask
{
    public function setup()
    {
        $this->addBlock('PORTFOLIO',1,1,'Portfolio setup');
        $this->addTask('PORTFOLIO','ALL_PORTFOLIOS','Manage portfolios');
        $this->addTask('PORTFOLIO','SETUP_PERIODS','Manage portfolio reporting periods');

        $this->addBlock('CURRENCY',1,2,'Currency setup');
        $this->addTask('CURRENCY','SETUP_FOREX','Manage currency exchange rates');
        $this->addTask('CURRENCY','SETUP_CURRENCIES','Manage All currencies');
    }

    public function processTask($id,$param = []) {
        $error = '';
        $message = '';
        $n = 0;
        
        
        if($id === 'SETUP_PERIODS') {
            $location = 'period';
            header('location: '.$location);
            exit;
        }

        if($id === 'SETUP_FOREX') {
            $location = 'forex';
            header('location: '.$location);
            exit;
        }

        if($id === 'SETUP_CURRENCIES') {
            $location = 'currency';
            header('location: '.$location);
            exit;
        }
        
        if($id === 'ALL_PORTFOLIOS') {
            $location = 'portfolio';
            header('location: '.$location);
            exit;
        }
           
    }
}