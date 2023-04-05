<?php
namespace Crm\Apis\Controllers;
use DB;
use App\Agmnf;
use App\Banks;
use App\Cause;
use App\Clhmn;
use App\Title;
use App\Branch;
use App\Client;
use App\Clparam;
use App\Country;
use App\Pipstmp;
use App\Currency;
use App\Polmaster;
use App\ClassModel;
use App\Occupation;
use App\Bankbranches;
use App\Identity_type;
use App\Vehiclemodelyear;
use Illuminate\Http\Request;
use Crm\Apis\Traits\ApiResponse;

use App\Http\Controllers\Controller;
use Crm\Apis\Exceptions\AimsException;


class GeneralApisController extends Controller{
    use ApiResponse;

    //get all classes of business
    public function classes(){
        try {
            $data = ClassModel::select('class', 'dept', 'description', 'stamp_duty', 'sticker_fees','motor_policy')->get();
            // $data = ClassModel::all();

            return $this->successResponse($data,'Successful');
        } catch (AimsException $e) {
            // dd($e);
            return $e->render($e);
        }
    }

    //get all branches
    public function branches(){
        try {
            $data = Branch::select('branch', 'description')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //get all clients
    public function clients(){
        try {
            $data = Client::select('client_number', 'client_type', 'name', 'id_number', 'pin_number','crm_flag')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //get all agents
    public function agents(){
        try {
            $data = Agmnf::select('name', 'branch', 'agent')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //get all taxes
    public function taxes(){
        try {
            $data = Pipstmp::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //policy details
    public function policyDetails($policy_no){
        try {
            $data = Polmaster::select('policy_no','endorse_no','period_from','period_to','sum_insured','renewal_premium','status')
                ->where('policy_no', $policy_no)->first();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //claim details
    public function claimDetails($claim_no){
        try {
            $data = Clhmn::select('claim_no','policy_no','endt_renewal_no','orig_total_estimate','curr_total_estimate','cost_todate','sum_insured','closed')
            ->where('claim_no', $claim_no)->firstOrFail();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //claim causes details
    public function causes(){
        try {
            $data = Cause::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //service provider details
    public function serviceProviders(){
        try {
            $data = Clparam::select('record_type', 'claimant_code', 'e_mail', 'name')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //vehicle models
    public function vehicleModels(){
        try {
            $data = Vehiclemodelyear::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //countries details
    public function countries(){
        try {
            $data = Country::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //client occupations
    public function occupations(){
        try {
            $data = Occupation::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //client occupations
    public function identityType(){
        try {
            $data = Identity_type::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //client occupations
    public function title(){
        try {
            // $data = Title::select(trim('title_code'), trim('title'), trim('sex'))->get();
            $data = DB::table('title')->select(DB::raw('trim(title_code) as title_code, trim(title) as title, trim(sex) as sex'))->get();
            return $this->successResponse($data,'Successful');
        } catch (AimsException $e) {
            return $e->render($e);
        }
    }

    //bank details
    public function banks(){
        try {
            $data = Banks::select('bank_code', 'description')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

     //bank branches
     public function bankBranches(){
        try {
            $data = Bankbranches::select('bank_code', 'branch_code', 'branch')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    //currency
    public function currency(){
       try {
           $data = Currency::all();

           return $this->successResponse($data,'Successful');
       } catch (\Throwable $e) {
           return $e->render($e);
       }
   }

}


?>