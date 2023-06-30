<?php

use Crm\Apis\Controllers\RegisterClientController;
use Crm\Apis\Controllers\GeneralApisController;
use Crm\Apis\Controllers\UnderwritingController;

use Illuminate\Support\Facades\Route;

// ::middleware('auth:sanctum')
Route::prefix('aims/api/v1/')->group(function(){
    Route::prefix('request/')->group(function(){
        // Route::middleware('auth:sanctum')->group(function () {
            Route::get('classes', [GeneralApisController::class, 'classes']);
            Route::get('branches', [GeneralApisController::class, 'branches']);
            Route::get('clients', [GeneralApisController::class, 'clients']);
            Route::get('client/client_no', [GeneralApisController::class, 'fetchClientByClientNo']);
            Route::get('client/client_pin', [GeneralApisController::class, 'fetchClientByClientPin']);
            Route::get('client/client_nid', [GeneralApisController::class, 'fetchClientByClientID']);
            Route::get('client/client_name', [GeneralApisController::class, 'fetchClientByClientName']);
            Route::get('client/exists', [GeneralApisController::class, 'clientExists']);
            Route::get('clientid/exists', [GeneralApisController::class, 'clientExistsID']);
            Route::get('clientpin/exists', [GeneralApisController::class, 'clientExistsPin']);
            Route::get('agents', [GeneralApisController::class, 'agents']);
            Route::get('agent/details', [GeneralApisController::class, 'agentObject']);
            Route::get('agent/exists', [GeneralApisController::class, 'agentExists']);
            Route::get('verify/vehicle/reg_no', [GeneralApisController::class, 'verifyVehicleRegNo']);
            Route::get('verify/vehicle/chassis_no', [GeneralApisController::class, 'verifyVehicleChassis']);
            Route::get('verify/vehicle/engine_no', [GeneralApisController::class, 'verifyVehicleEngine']);
            Route::get('verify/policy', [GeneralApisController::class, 'verifyPolicy']);
            Route::get('policy/riskitem', [GeneralApisController::class, 'fetchRiskItems']);
            Route::get('pipstmp', [GeneralApisController::class, 'taxes']);
            Route::get('causes', [GeneralApisController::class, 'causes']);
            Route::get('clauses', [GeneralApisController::class, 'classClauses']);
            Route::get('serviceproviders', [GeneralApisController::class, 'serviceProviders']);
            Route::get('serviceprovider/details', [GeneralApisController::class, 'serviceProviderObject']);
            Route::get('vehiclemodels', [GeneralApisController::class, 'vehicleModels']);
            Route::get('countries', [GeneralApisController::class, 'countries']);
            Route::get('occupations', [GeneralApisController::class, 'occupations']);
            Route::get('title', [GeneralApisController::class, 'title']);
            Route::get('currency', [GeneralApisController::class, 'currency']);
            Route::get('policies', [GeneralApisController::class, 'getPolicies']);
            Route::get('sections', [GeneralApisController::class, 'getSections']);
            Route::get('motor/premiumrates', [GeneralApisController::class, 'motorPremiumRates']);
            Route::get('motor/premiumgroups', [GeneralApisController::class, 'motorPremiumGroups']);
            Route::get('banks', [GeneralApisController::class, 'banks']);
            Route::get('bank/branches', [GeneralApisController::class, 'bankBranches']);
            Route::get('identity/type', [GeneralApisController::class, 'identityType']);
            Route::get('claim/{claim_no}', [GeneralApisController::class, 'claimDetails']);
            Route::get('policy/{policy_no}', [GeneralApisController::class, 'policyDetails']);
            Route::post('register/client', [RegisterClientController::class, 'index']);
            Route::post('generate/policy', [UnderwritingController::class, 'generatePolicy']);
        // });
    });
});


?>