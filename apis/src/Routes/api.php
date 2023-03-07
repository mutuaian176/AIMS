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
        Route::get('causes', [GeneralApisController::class, 'causes']);
        Route::get('serviceproviders', [GeneralApisController::class, 'serviceProviders']);
        Route::get('vehiclemodels', [GeneralApisController::class, 'vehicleModels']);
        Route::get('countries', [GeneralApisController::class, 'countries']);
        Route::get('occupations', [GeneralApisController::class, 'occupations']);
        Route::get('title', [GeneralApisController::class, 'title']);
        Route::get('banks', [GeneralApisController::class, 'banks']);
        Route::get('bank/branches', [GeneralApisController::class, 'bankBranches']);
        Route::get('identity/type', [GeneralApisController::class, 'identityType']);
        Route::get('claim/{claim_no}', [GeneralApisController::class, 'claimDetails']);
        Route::get('policy/{policy_no}', [GeneralApisController::class, 'policyDetails']);
        Route::post('register/client', [RegisterClientController::class, 'index']);
    });
});


?>