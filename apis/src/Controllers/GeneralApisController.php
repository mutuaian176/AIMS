<?php
namespace Crm\Apis\Controllers;
use App\Http\Controllers\Controller;
use Crm\Apis\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\ClassModel;
use App\Branch;
use App\Client;
use App\Agmnf;
use App\Pipstmp;
use App\Polmaster;
use App\Clhmn;


class GeneralApisController extends Controller{
    use ApiResponse;

    //get all classes of business
    public function classes(){
        try {
            $resp = ClassModel::select('class', 'description')->get();

            return $this->successResponse($resp,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //get all branches
    public function branches(){
        try {
            $resp = Branch::select('branch', 'description')->get();

            return $this->successResponse($resp,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //get all clients
    public function clients(){
        try {
            $resp = Client::select('client_number', 'client_type', 'name')->get();

            return $this->successResponse($resp,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //get all agents
    public function agents(){
        try {
            $resp = Agmnf::select('name', 'branch', 'agent')->get();

            return $this->successResponse($resp,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //get all taxes
    public function taxes(){
        try {
            $resp = Pipstmp::all();

            return $this->successResponse($resp,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //policy details
    public function policyDetails($policy_no){
        try {
            $resp = Polmaster::select('policy_no','endorse_no','period_from','period_to','sum_insured','renewal_premium','status')->where('policy_no', $policy_no)->first();

            return $this->successResponse($resp,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //claim details
    public function claimDetails($claim_no){
        try {
            $resp = Clhmn::select('claim_no','policy_no','endt_renewal_no','orig_total_estimate','curr_total_estimate','cost_todate','sum_insured','closed')
            ->where('claim_no', $claim_no)->firstOrFail();

            return $this->successResponse($resp,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

}


?>