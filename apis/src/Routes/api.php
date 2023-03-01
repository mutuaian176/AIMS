<?php

use Crm\Apis\Controllers\RegisterClientController;
use Crm\Apis\Controllers\GeneralApisController;


Route::prefix('aims/api/v1/')->group(function(){
    Route::prefix('request/')->group(function(){
        Route::get('classes', [GeneralApisController::class, 'classes']);
        Route::get('branches', [GeneralApisController::class, 'branches']);
        Route::get('clients', [GeneralApisController::class, 'clients']);
        Route::get('agents', [GeneralApisController::class, 'agents']);
        Route::get('pipstmp', [GeneralApisController::class, 'taxes']);
        Route::get('claim/{claim_no}', [GeneralApisController::class, 'claimDetails']);
        Route::get('policy/{policy_no}', [GeneralApisController::class, 'policyDetails']);
        Route::post('register/client', [RegisterClientController::class, 'index']);
    });
});


?>