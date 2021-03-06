<?php
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/setup_app.php file within this framework
add the below code snippet to the end of existing "src/setup_app.php" file.
This tells the framework about module: name, sub-memnu route list and title, database table prefix.
*/

$container['config']->set('module','asset',['name'=>'Asset manager',
                                            'route_root'=>'admin/asset/',
                                            'route_list'=>['dashboard'=>'Dashboard','trade'=>'Trades','cash'=>'Cashflows',
                                                           'asset'=>'Assets','task'=>'Tasks','report'=>'Reports'],
                                            'table_prefix'=>'ass_'
                                            ]);


