<?php  
/*
NB: This is not stand alone code and is intended to be used within "seriti/slim3-skeleton" framework
The code snippet below is for use within an existing src/routes.php file within this framework
copy the "/asset" group into the existing "/admin" group within existing "src/routes.php" file 
*/

$app->group('/admin', function () {

    $this->group('/asset', function () {
        $this->any('/asset', \App\Asset\AssetController::class);
        $this->any('/asset_note', \App\Asset\AssetNoteController::class);
        $this->any('/asset_price', \App\Asset\AssetPriceController::class);
        $this->any('/asset_file', \App\Asset\AssetFileController::class);
        $this->any('/asset_image', \App\Asset\AssetImageController::class);
        $this->any('/currency', \App\Asset\CurrencyController::class);
        $this->any('/forex', \App\Asset\ForexController::class);
        $this->any('/dashboard', \App\Asset\DashboardController::class);
        $this->any('/trade', \App\Asset\TradeController::class);
        $this->any('/cash', \App\Asset\CashController::class);
        $this->any('/price', \App\Asset\PriceController::class);
        $this->any('/period', \App\Asset\PeriodController::class);
        $this->any('/portfolio', \App\Asset\PortfolioController::class);
        $this->any('/report', \App\Asset\ReportController::class);
        $this->any('/task', \App\Asset\TaskController::class);
        $this->post('/ajax', \App\Asset\Ajax::class);
        $this->get('/setup_data', \App\Asset\SetupDataController::class);
    })->add(\App\Asset\Config::class);

})->add(\App\ConfigAdmin::class);



