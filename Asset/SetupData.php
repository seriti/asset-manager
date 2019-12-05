<?php
namespace App\Asset;

use Seriti\Tools\SetupModuleData;
use Seriti\Tools\CURRENCY_ID;

use App\Asset\Helpers;

class SetupData extends SetupModuledata
{

    public function setupSql()
    {
        $this->tables = ['portfolio','asset','currency','transact','period','file','note','price','forex'];

        $this->addCreateSql('portfolio',
                            'CREATE TABLE `TABLE_NAME` (
                              `portfolio_id` int(11) NOT NULL AUTO_INCREMENT,
                              `name` varchar(255) NOT NULL,
                              `description` text NOT NULL,
                              `currency_id` varchar(4) NOT NULL,
                              `status` varchar(64) NOT NULL,
                              `date_start` date NOT NULL,
                              `date_end` date NOT NULL,
                              `calc_timestamp` datetime NOT NULL,
                              PRIMARY KEY (`portfolio_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'); 

        $this->addCreateSql('asset',
                            'CREATE TABLE `TABLE_NAME` (
                              `asset_id` int(11) NOT NULL AUTO_INCREMENT,
                              `portfolio_id` int(11) NOT NULL,
                              `name` varchar(255) NOT NULL,
                              `description` text NOT NULL,
                              `status` varchar(64) NOT NULL,
                              `type_id` varchar(64) NOT NULL,
                              `currency_id` varchar(4) NOT NULL,
                              PRIMARY KEY (`asset_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'); 

        $this->addCreateSql('currency',
                            'CREATE TABLE `TABLE_NAME` (
                              `currency_id` varchar(4) NOT NULL,
                              `name` varchar(64) NOT NULL,
                              `symbol` varchar(8) NOT NULL,
                              `risk_free_rate` decimal(8,2) NOT NULL,
                              `status` varchar(64) NOT NULL,
                              PRIMARY KEY (`currency_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'); 

         $this->addCreateSql('forex',
                            'CREATE TABLE `TABLE_NAME` (
                              `forex_id` int(11) NOT NULL AUTO_INCREMENT,
                              `currency_id_portfolio` varchar(4) NOT NULL,
                              `currency_id_transact` varchar(4) NOT NULL,
                              `year` int(11) NOT NULL,
                              `month` int(11) NOT NULL,
                              `rate` decimal(12,5) NOT NULL,
                              PRIMARY KEY (`forex_id`),
                              UNIQUE KEY `idx_ass_forex1` (`currency_id_portfolio`,`currency_id_transact`,`year`,`month`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'); 

        $this->addCreateSql('price',
                            'CREATE TABLE `TABLE_NAME` (
                                `price_id` int(11) NOT NULL AUTO_INCREMENT,
                                `portfolio_id` int(11) NOT NULL,
                                `asset_id` varchar(64) NOT NULL,
                                `year` int(11) NOT NULL,
                                `month` int(11) NOT NULL,
                                `price` decimal(16,5) NOT NULL,
                                PRIMARY KEY (`price_id`)
                            ) ENGINE=MyISAM DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('note',
                            'CREATE TABLE `TABLE_NAME` (
                                `note_id` int(11) NOT NULL AUTO_INCREMENT,
                                `location_id` varchar(64) NOT NULL,
                                `date` datetime NOT NULL,
                                `note` text NOT NULL,
                                PRIMARY KEY (`note_id`),
                                UNIQUE KEY `idx_ass_note` (`location_id`)
                            ) ENGINE=MyISAM DEFAULT CHARSET=utf8'); 

        $this->addCreateSql('transact',
                            'CREATE TABLE `TABLE_NAME` (
                              `transact_id` int(11) NOT NULL AUTO_INCREMENT,
                              `asset_id` int(11) NOT NULL,
                              `asset_id_link` int(11) NOT NULL,
                              `date_create` datetime NOT NULL,
                              `date` datetime NOT NULL,
                              `type_id` varchar(64) NOT NULL,
                              `nominal` decimal(16,5) unsigned NOT NULL,
                              `price` decimal(16,5) NOT NULL,
                              `amount` decimal(16,2) NOT NULL,
                              `description` varchar(255) NOT NULL,
                              `portfolio_id` int(11) NOT NULL,
                              `status` varchar(64) NOT NULL,
                              PRIMARY KEY (`transact_id`),
                              UNIQUE KEY `idx_ass_transact1` (`portfolio_id`,`asset_id`,`date`,`type_id`,`nominal`,`description`),
                              KEY `fk_ass_transact_1` (`portfolio_id`),
                              KEY `fk_ass_transact_2` (`asset_id`),
                              CONSTRAINT `fk_ass_transact_1` FOREIGN KEY (`portfolio_id`) REFERENCES `TABLE_PREFIXportfolio` (`portfolio_id`) ON UPDATE NO ACTION,
                              CONSTRAINT `fk_ass_transact_2` FOREIGN KEY (`asset_id`) REFERENCES `TABLE_PREFIXasset` (`asset_id`) ON UPDATE NO ACTION
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'); 

        $this->addCreateSql('period',
                            'CREATE TABLE `TABLE_NAME` (
                              `period_id` int(11) NOT NULL AUTO_INCREMENT,
                              `portfolio_id` int(11) NOT NULL,
                              `date_start` date NOT NULL,
                              `date_end` date NOT NULL,
                              `status` varchar(64) NOT NULL,
                              `name` varchar(255) NOT NULL,
                              PRIMARY KEY (`period_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;'); 

        $this->addCreateSql('portfolio',
                            'CREATE TABLE `TABLE_NAME` (
                              `portfolio_id` int(11) NOT NULL AUTO_INCREMENT,
                              `name` varchar(255) NOT NULL,
                              `description` text NOT NULL,
                              `currency_id` varchar(4) NOT NULL,
                              `status` varchar(64) NOT NULL,
                              `date_start` date NOT NULL,
                              `date_end` date NOT NULL,
                              `calc_timestamp` datetime NOT NULL,
                              PRIMARY KEY (`portfolio_id`)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');  

        $this->addCreateSql('file',
                            'CREATE TABLE `TABLE_NAME` (
                              `file_id` int(10) unsigned NOT NULL,
                              `title` varchar(255) NOT NULL,
                              `file_name` varchar(255) NOT NULL,
                              `file_name_orig` varchar(255) NOT NULL,
                              `file_text` longtext NOT NULL,
                              `file_date` date NOT NULL DEFAULT \'0000-00-00\',
                              `location_id` varchar(64) NOT NULL,
                              `location_rank` int(11) NOT NULL,
                              `key_words` text NOT NULL,
                              `description` text NOT NULL,
                              `file_size` int(11) NOT NULL,
                              `encrypted` tinyint(1) NOT NULL,
                              `file_name_tn` varchar(255) NOT NULL,
                              `file_ext` varchar(16) NOT NULL,
                              `file_type` varchar(16) NOT NULL,
                              PRIMARY KEY (`file_id`),
                              FULLTEXT KEY `search_idx` (`key_words`)
                            ) ENGINE=MyISAM DEFAULT CHARSET=utf8');  

        //initialisation
        $this->addInitialSql('INSERT INTO `TABLE_PREFIXportfolio` (name,description,currency_id,status,date_start) '.
                             'VALUES("My Portfolio","My first portfolio","'.CURRENCY_ID.'","OK",CURDATE())');

        $this->addInitialSql('INSERT INTO `TABLE_PREFIXcurrency` (currency_id,name,symbol,risk_free_rate,status) '.
                             'VALUES("'.CURRENCY_ID.'","'.CURRENCY_ID.'","'.CURRENCY_ID.'","5","OK")');

        $this->addInitialSql('INSERT INTO `TABLE_PREFIXasset` (name,description,status,type_id,currency_id) '.
                             'VALUES("Bank/Trading account","Default bank/trading account","OK","CASH")');
        

        //updates use time stamp in ['YYYY-MM-DD HH:MM'] format, must be unique and sequential
        //$this->addUpdateSql('YYYY-MM-DD HH:MM','Update TABLE_PREFIX--- SET --- "X"');
    }


    protected function afterProcess() {
      /*
        $message = Helpers::setupXXXX($this->db,'ALL'); 
        if($message !=='' ) {
            $this->process_count++;
            $this->addMessage($message);
        }    
      */

    }
    
}
