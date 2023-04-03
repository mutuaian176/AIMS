<?php

namespace Crm\Apis\Providers;

// use App

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class CrmServiceProvider extends ServiceProvider{
    
    public function boot(){
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    }

    public function register(){

    }
}

?>