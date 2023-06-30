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
use App\Clauses;
use App\Pipstmp;
use App\Dcontrol;
use App\Polsect;
use App\Currency;
use App\Polmaster;
use App\Classsect;
use App\Polmasterend;
use App\ClassModel;
use App\Occupation;
use App\Bankbranches;
use App\Models\Motorsect;
use App\Models\Modtlmast;
use App\Models\Motorprem_grp;
use App\Identity_type;
use App\Vehiclemodelyear;
use Illuminate\Http\Request;
use Crm\Apis\Traits\ApiResponse;

use App\Http\Controllers\Controller;
use Crm\Apis\Exceptions\AimsException;
use Illuminate\Support\Facades\Validator;


class GeneralApisController extends Controller{
    use ApiResponse;
        /**
        * @OA\Get(
        * path="/aims/api/v1/request/classes",
        * operationId="Classes",
        * tags={"Classes"},
        * summary="User Classes",
        * description="Business Classes here",
        *      @OA\Response(
        *          response=201,
        *          description="Fetched Successfully",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=200,
        *          description="Fetched Successfully",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(
        *          response=422,
        *          description="Unprocessable Entity",
        *          @OA\JsonContent()
        *       ),
        *      @OA\Response(response=400, description="Bad request"),
        *      @OA\Response(response=404, description="Resource Not Found"),
        * )
        */

    /***********get all classes of business***********/
    public function classes(){
        try {
            $data = ClassModel::select('class', 'dept', 'description', 'stamp_duty', 'sticker_fees','motor_policy','personal', 'accident',
            'fire', 'marine', 'travel', 'marine_cargo', 'bond', 'earthquake', 'liability_class', 'bypass_location', 'items_schedule', 'domestic_package', 'engineering', 'burglary')
            ->where('dept', '<>', 0)->where('class','<>', 127)->get();

            return $this->successResponse($data,'Successful');
        } catch (AimsException $e) {
            return $e->render($e);
        }
    }

    /***********get all branches***********/
    public function branches(){
        try {
            $data = Branch::select('branch', 'description')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********get all clients***********/
    public function clients(){
        try {
            $data = Client::select('client_number', 'client_type', 'name', 'id_number', 'pin_number','crm_flag')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********client details by client number***********/
    public function fetchClientByClientNo(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "client_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            
            $data = Client::select('client_type', 'name', 'pin_number', 'blacklist_flag')
                    ->where('client_number', $request->client_no)
                    ->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********client details by pin***********/
    public function fetchClientByClientPin(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "client_pin" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            
            $data = Client::select('client_type', 'name', 'pin_number', 'blacklist_flag')
                ->where('id_number', $request->client_pin)
                ->where('identity_type', 'T')
                ->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********client details by national ID***********/
    public function fetchClientByClientID(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "client_id" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            
            $data = Client::select('client_type', 'name', 'pin_number', 'blacklist_flag')
                    ->where('id_number', $request->client_id)
                    ->where('identity_type', 'I')
                    ->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********client details by name***********/
    public function fetchClientByClientName(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "client_name" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            
            $data = Client::select('client_type', 'name', 'pin_number', 'blacklist_flag')
                    ->where('name', 'LIKE' ,'%'.$request->client_name.'%')
                    ->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********client exists check***********/
    public function clientExists(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "client_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Client::where('client_number', $request->client_no)->exists();

            if ($exists) {
                $data= ["clientFound"=>True];
                return $this->successResponse($data,$msg='Client Exists');
            } else {
                $data= ["clientFound"=>False];
                return $this->successResponse($data,$msg='Client Does Not Exist',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********client exists check by national ID***********/
    public function clientExistsID(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "client_id" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Client::where('id_number', $request->client_id)->exists();

            if ($exists) {
                $data= ["clientFound"=>True];
                return $this->successResponse($data,$msg='Client Exists');
            } else {
                $data= ["clientFound"=>False];
                return $this->successResponse($data,$msg='Client Does not exist',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    

    /***********client exists check by national Pin***********/
    public function clientExistsPin(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "client_pin" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Client::where('pin_number', $request->client_pin)->exists();

            if ($exists) {
                $data= ["clientFound"=>True];
                return $this->successResponse($data,$msg='Client Exists');
            } else {
                $data= ["clientFound"=>False];
                return $this->successResponse($data,$msg='Client Does Not Exist',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********get all agents***********/
    public function agents(){
        try {
            $data = Agmnf::select('name', 'branch as branchNumber', 'agent as agentNumber')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********agent details***********/
    public function agentObject(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "agent" => 'required',
                "branch" => 'required',
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $data = Agmnf::select('name', 'stop_flag as active', 'vat_on_comm', 'pin_number')
                    ->where('agent', $request->agent)
                    ->where('branch', $request->branch)
                    ->get();

            return $this->successResponse($data,'Successful');
        } catch (AimsException $e) {
            return $e->render($e);
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********agent exists check***********/
    public function agentExists(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "agent" => 'required',
                "branch" => 'required',
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Agmnf::where('agent', $request->agent)
                    ->where('branch', $request->branch)->exists();
            
            if ($exists) {
                $data= ["agentFound"=>True];
                return $this->successResponse($data,$msg='Agent Exists');
            } else {
                $data= ["agentFound"=>False];
                return $this->successResponse($data,$msg='Agent Does Not Exist',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }


    /***********get all taxes***********/
    public function taxes(){
        try {
            $data = Pipstmp::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********policy details***********/
    public function policyDetails($policy_no){
        try {
            $data = Polmaster::select('policy_no','endorse_no','period_from','period_to','sum_insured','renewal_premium','status')
                ->where('policy_no', $policy_no)->first();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********claim details***********/
    public function claimDetails($claim_no){
        try {
            $data = Clhmn::select('claim_no','policy_no','endt_renewal_no','orig_total_estimate','curr_total_estimate','cost_todate','sum_insured','closed')
            ->where('claim_no', $claim_no)->firstOrFail();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********claim causes details***********/
    public function causes(){
        try {
            $data = Cause::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********service provider details***********/
    public function serviceProviders(){
        try {
            $data = Clparam::select('record_type', 'claimant_code', 'e_mail', 'name')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********service provider details***********/
    public function serviceProviderObject(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "sp_code" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $data = Clparam::select('name', 'e_mail', 'record_type')
                    ->where('claimant_code', $request->sp_code)
                    ->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********vehicle models***********/
    public function vehicleModels(){
        try {
            $data = Vehiclemodelyear::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********countries details***********/
    public function countries(){
        try {
            $data = Country::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********client occupations***********/
    public function occupations(){
        try {
            $data = Occupation::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }
    
    /***********client identity types***********/
    public function identityType(){
        try {
            $data = Identity_type::all();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }
    
    /***********client titles***********/
    public function title(){
        try {
            // $data = Title::select(trim('title_code'), trim('title'), trim('sex'))->get();
            $data = DB::table('title')->select(DB::raw('trim(title_code) as title_code, trim(title) as title, trim(sex) as sex'))->get();
            return $this->successResponse($data,'Successful');
        } catch (AimsException $e) {
            return $e->render($e);
        }
    }

    /***********banks list***********/
    public function banks(){
        try {
            $data = Banks::select('bank_code', 'description')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********banks branches list***********/
     public function bankBranches(){
        try {
            $data = Bankbranches::select('bank_code', 'branch_code', 'branch')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

    /***********currencies***********/
    public function currency(){
       try {
           $data = Currency::all();

           return $this->successResponse($data,'Successful');
       } catch (\Throwable $e) {
           return $e->render($e);
       }
   }

    /***********policies list***********/
   public function getPolicies(){
      try {
          $data = Polmasterend::select('agent_no', 'branch', 'policy_no', 'endorse_no', 'client_number', 'period_from', 'period_to',
                    'class', 'sum_insured','incept_date', 'expiry_date', 'renewal_date', 'annual_premium')
                    // ->where('cancelled','<>','Y')
                    ->where('agent_no', 222)
                    ->where('branch', 10)
                    ->get();

          return $this->successResponse($data,'Successful');
      } catch (\Throwable $e) {
          return $e->render($e);
      }
  }
   
    /***********Non motor sections***********/
    public function getSections(){
        try {
            $data = Classsect::select('class', 'section_no', 'section_code', 'section_description', 'classgrp', 'pick_highest', 'apply_on_units',
                        'travelplan', 'rate', 'add_to_section' , 'min_rate', 'apply_earthquake', 'none_proratable', 'total_si', 'si_type')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

   
    /***********motor premium rates***********/
    public function motorPremiumRates(){
        try {
            $data = Motorsect::select('motorsect.class', 'item_code', 'motorsect.grp_code as group_code', 'motorprem_grp.description as group_description', 
                        'motorsect.covertype as covertype_code', 'covertype.cover_description', 'classtype.classtype', 'classtype.description as classtype_description',
                        'basis', 'rate_amount',
                        'minimum_premium')
                        ->join('motorprem_grp', function($query){
                            $query->on('motorprem_grp.grp_code','=','motorsect.grp_code');
                        })
                        ->join('covertype', function($query1){
                            $query1->on('covertype.cover','=','motorsect.covertype');
                        })
                        ->join('classtype', function($query2){
                            $query2->on('classtype.class','=','motorsect.class');
                        })
                        ->join('classtype', function($query3){
                            $query3->on('classtype.classtype','=','motorsect.classtype');
                        })
                        ->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

   
    /***********fetch clauses***********/
    public function classClauses(){
        try {
            $data = Clauses::select('clauses.class as product_code', 'class.description as product_name' , 
                        'clauses.dept as class_code', 'dept.description as class_name' ,'clauses.clause as clause_code', 
                        'clauses.description')
                        ->join('dept', function($query){
                            $query->on('dept.dept', '=', 'clauses.dept');
                        })
                        ->join('class', function($query){
                            $query->on('class.class', '=', 'clauses.class');
                        })
                        ->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

   
    /***********risk items per policy***********/
    public function fetchRiskItems(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "policy_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $policy = Polmaster::where('policy_no', $request->policy_no)->first();
            $class = ClassModel::where('class', $policy->class)->first();

            if ($class->motor_policy == 'Y') {
                $risk = Modtlmast::select('modtlmast.reg_no', 'modtlmast.make', 'modtlmast.model', 'modtlmast.engine_no', 
                        'modtlmast.chassis_no', 'modtlsumm.sum_insured', 'modtlsumm.annual_premium')
                        ->join('modtlsumm', function($query){
                            $query->on('modtlsumm.reg_no', '=', 'modtlmast.reg_no');
                        })
                        ->where('modtlmast.policy_no', $request->policy_no)
                        ->where('modtlmast.status', 'ACT')
                        ->get();
            }else{
                $risk = Polsect::select('name', 'plot_no', 'town', 'street','sum_insured', 'annual_premium')
                        ->where('policy_no', $request->policy_no)
                        ->get();
            }
            $risk = ["risk_items"=>$risk];

            return $this->successResponse($risk,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }
    
    /***********motor premium groups***********/
    public function motorPremiumGroups(){
        try {
            $data = Motorprem_grp::select('grp_code', 'description', 'mandatory', 'status', 'refundable', 'basic_premium')->get();

            return $this->successResponse($data,'Successful');
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }
    
    /***********verify registration number***********/
    public function verifyVehicleRegNo(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "reg_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Modtlmast::where('reg_no', $request->reg_no)
                    ->where('status', 'ACT')->exists();
            $data= [];
            if ($exists) {
                $data= ["regNoFound"=>True];
                return $this->successResponse($data,$msg='Vehicle active');
            } else {
                $data= ["regNoFound"=>False];
                return $this->successResponse($data,$msg='Vehicle Not active',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }
    
    /***********verify Chassis number***********/
    public function verifyVehicleChassis(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "chassis_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Modtlmast::where('chassis_no', $request->chassis_no)
                    ->where('status', 'ACT')->exists();
            
            if ($exists) {
                $data= ["chassisFound"=>True];
                return $this->successResponse($data,$msg='Vehicle active');
            } else {
                $data= ["chassisFound"=>False];
                return $this->successResponse($data,$msg='Vehicle Not active',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }
    
    /***********verify Engine number***********/
    public function verifyVehicleEngine(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "engine_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Modtlmast::where('engine_no', $request->engine_no)    
                    ->where('status', 'ACT')->exists();
            
            if ($exists) {
                $data= ["engineFound"=>True];
                return $this->successResponse($data,$msg='Vehicle active');
            } else {
                $data= ["engineFound"=>False];
                return $this->successResponse($data,$msg='Vehicle Not active',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }
    
    /***********verify policy number***********/
    public function verifyPolicy(Request $request){
        try {
            $validated = Validator::make($request->all(),[
                "policy_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $exists = Polmaster::where('policy_no', $request->policy_no)    
                    ->where('status', 'N')->exists();
            $data= [];
            if ($exists) {
                return $this->successResponse($data,$msg='Policy exists');
            } else {
                return $this->successResponse($data,$msg='Policy Does Not Exist',$aimstatus="AIMS003");
            }
        } catch (\Throwable $e) {
            return $e->render($e);
        }
    }

}


?>