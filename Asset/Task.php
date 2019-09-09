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
        $this->addTask('ALL_PORTFOLIOS','Manage portfolios');
        $this->addTask('SETUP_PERIODS','Manage portfolio reporting periods');

        $this->addTask('SETUP_FOREX','Manage currency exchange rates');
        $this->addTask('SETUP_CURRENCIES','Manage All currencies');
    }

    function processTask($id,$param = []) {
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
?>
                                                
