<?php
namespace Crm\Apis\Controllers;

use DateTime;
use App\Agmnf;
use App\Tran0;
use App\Client;
use App\Dtran0;
use App\Bustype;
use App\Classbr;
use App\Country;
use App\Curr_ate;
use App\Currency;
use App\Dcontrol;
use App\Transtype;
use Carbon\Carbon;
use App\ClassModel;
use Illuminate\Http\Request;
use Crm\Apis\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Crm\Apis\Rules\NewMotorRequest;
use App\Http\Controllers\Controller;
use Crm\Apis\Controllers\ExpressDebit;
use Illuminate\Support\Facades\Validator;
use Crm\Apis\Controllers\MotorRiskController;
use App\Http\Controllers\gb\underwriting\Policy;
use App\Http\Controllers\gb\underwriting\Policy_details;


class UnderwritingController extends Controller{
    use apiResponse;
    public function generatePolicy(Request $request){
        DB::beginTransaction();
        try {
            
            $validated = Validator::make($request->policy,[
                "agentpol" => 'required',
                "branchpol" => 'required',
                "cls" => 'required',
                "period_from" => 'required',
                "period_to" => 'required',
                "type" => 'required',
                "bustype" => 'required',
                "ast" => 'required',
                "currency" => 'required',
                "client_no" => 'required'
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $pol_det = $request->policy;

            /************validate class************/
            $class_exist = $this->validateClass($pol_det['cls']);
            if ($class_exist['status'] == 0) {
                return $this->errorResponse($class_exist['message']);
            }

            $cls = ClassModel::where('class', $pol_det['cls'])->first();
                  
            $uw_year = date('Y', strtotime((string) $pol_det['period_from']));
    
            if($cls->exem == "Y"){
                $renewal = new DateTime((string) $pol_det['period_to']);
                $renewal->modify('+1 day');
            }
            else{
                $renewal = new DateTime((string) $pol_det['period_to']);
            }
    
            /***********dcon_no ***********/
            $transaction_no = Tran0::where('rec_no', 0)->get(['tran_no']);
            $tran_no = $transaction_no[0]->tran_no;
            $tran0 = Tran0::where('rec_no', 0)->increment('tran_no', (int) '1');
            
            /*********** get dtrans_no and period ***********/
            $doc_trans = Dtran0::where('rec_no', 0)->get(['dtran_no', 'account_month', 'account_year']);
            $dtran_no = $doc_trans[0]->dtran_no;
            $account_month = $doc_trans[0]->account_month;
            $account_year = $doc_trans[0]->account_year;
            $dtran0 = Dtran0::where('rec_no', 0)->increment('dtran_no', (int) '1');

            $policy_obj = new Policy();
            
            /************validate client************/
            $client_exist = $this->validateClient($pol_det['client_no']);
            if ($client_exist['status'] == 0) {
                return $this->errorResponse($client_exist['message']);
            }

            /************validate branch agent************/
            $agent_exist = $this->validateAgent($pol_det['branchpol'], $pol_det['agentpol']);
            if ($agent_exist['status'] == 0) {
                return $this->errorResponse($agent_exist['message']);
            }
            $new_endt_renewal_no = $policy_obj->generate_pol(
                $pol_det['branchpol'],
                $pol_det['cls'],
                $pol_det['type'],
                $account_year,
                $account_month
            );
    
            /***********get currency***********/
            $currency = $pol_det['currency'];
            $currency_code = $currency;

            /***********get current currency rate***********/
            $currency_rate = $pol_det['currency_rate'];
    
            $date = Carbon::today(); 
            $currency = Currency::where('currency_code', $currency_code)->get(['currency', 'base_currency']);
            $currency = $currency[0];

    
            /***********get class department***********/
            $dept = (string) $cls->dept;
            $motor_policy = trim($cls->motor_policy);
    
            /***********get number of days of cover***********/
            $cover_from = Carbon::parse($pol_det['period_from']);
            $cover_to = Carbon::parse($pol_det['period_to']);
    
            $days_of_cover = $cover_from->diffInDays($cover_to);
    
    
            $prop_number = Classbr::where('class', $cls->class)->get(['prop_serial']);
            $prop_no = $prop_number[0]->prop_serial;
            $dprop_no = Classbr::where('class', $cls->class);
    
            $dprop_no = Classbr::where('class', $cls->class)->increment('prop_serial', (int) '1');
    
    
            $seq_no = Dcontrol::generateTranseqNumber($pol_det['type'],$polno->policy_no);
            /***********insured array***********/
    
            $insured = Client::where('client_number', $pol_det['client_no'])->get(['name', 'client_number', 'client_type']);
    
            /***********get doc type ***********/
            $document_type = Transtype::where('descr', $pol_det['type'])->get(['doc_type']);
            $doc_type = $document_type[0]->doc_type;
    
            /************type of bus************/
            $bustype_curr = Bustype::where('type_of_bus', trim($pol_det['bustype']))->get();
            $bustype_curr = $bustype_curr[0];
    
            /************add new record to dcontrol************/
            $dcontrol = new Dcontrol;
            $dcontrol->dcon_no = $tran_no;
            $dcontrol->transeq_no = $seq_no;
            $dcontrol->doc_type = strtoupper($doc_type);
            $dcontrol->dprop_no=$prop_no;
            $dcontrol->policy_no=(string)$new_endt_renewal_no;
            $dcontrol->prop_date=Carbon::now();
            $dcontrol->branch=$pol_det['branchpol'];
            $dcontrol->agent=$pol_det['agentpol'];
            $dcontrol->class=$cls->class;
            $dcontrol->user_str='CRM_USER';
            $dcontrol->period_from=$pol_det['period_from'];
            $dcontrol->period_to=$pol_det['period_to'];
            $dcontrol->old_policy_no = $request->old_pol;
            $dcontrol->renew_old_policy = 'N';
    
            $dcontrol->doc_type = 'DRN';
    
            if(trim($request->ast) == 'T'){
                $dcontrol->period_from = $pol_det['t_period_from'];
                $dcontrol->period_to =$pol_det['t_period_to'];
                $dcontrol->cov_period_from = $pol_det['period_from'];
                $dcontrol->cov_period_to = $pol_det['period_to'];
                $dcontrol->endt_days = $pol_det['t_cover_days'];
            }
            else if(trim($pol_det['ast']) == 'S' && trim($pol_det['prem_method']) == 'S'){
                $dcontrol->period_from=$pol_det['t_period_from'];
                $dcontrol->period_to=$pol_det['t_period_to'];
                $dcontrol->cov_period_from=$pol_det['period_from'];
                $dcontrol->cov_period_to=$pol_det['period_to'];
                $dcontrol->endt_days = $pol_det['t_cover_days'];
                
                $shortTermRate = $policy_obj->verifyShortTermRate($pol_det['cls'],$pol_det['t_cover_days']);
    
            }else{
                $dcontrol->period_from=$pol_det['period_from'];
                $dcontrol->period_to=$pol_det['period_to'];
                $dcontrol->cov_period_from=$pol_det['period_from'];
                $dcontrol->cov_period_to=$pol_det['period_to'];
            }
    
            $dcontrol->short_term_method=$pol_det['prem_method'];
            $dcontrol->short_term_percent=$shortTermRate;
            $dcontrol->effective_date=$pol_det['period_from'];
            $dcontrol->branch_code= str_pad($pol_det['branchpol'], 3,"0",STR_PAD_LEFT);
            $dcontrol->co_insure=$pol_det['co_ins'];
            $dcontrol->ext_from = $dcontrol->period_from;
            $dcontrol->ext_to = $dcontrol->period_to;
    
            if($dcontrol->co_insure=='Y' && $pipcnam->coins_rate_per_sec!='Y'){
                $dcontrol->co_ins_rate=$pol_det['co_ins_rate'];
                $dcontrol->co_ins_base=$pol_det['co_ins_base'];
            }else{
                $dcontrol->co_ins_rate=0;
                $dcontrol->co_ins_base=0;
            }
            
            $dcontrol->type_of_bus=$pol_det['bustype'];
            $dcontrol->dept=$dept;
            $dcontrol->actual_period_from=$pol_det['period_from'];
            $dcontrol->actual_period_to=$pol_det['period_to'];
            $dcontrol->financed=$pol_det['financed'];
            $dcontrol->ipf=$pol_det['ipf'];
            $dcontrol->ast_marker=$pol_det['ast'];  
            $dcontrol->items_total=1;
            $dcontrol->branch_cod=$pol_det['branchpol'];
            $dcontrol->currency=$currency_code;
            $dcontrol->ipf_repayment_date=$pol_det['ipf_repayment_date'];
    
            if($pol_det['financed'] == 'Y'){
    
                $dcontrol->financed_code=$pol_det['financier'];
            }else{
                $dcontrol->financed_code='';
            }
    
            if ($pol_det['co_ins'] == 'Y') {
    
                $dcontrol->company_share = $pol_det['co_ins_share'];
            } else {
                $dcontrol->company_share = 100;
            }
    
            if($charge_vat == 'Y'){
                $dcontrol->vat_type = $get_vat_setup->vat_type;
                $dcontrol->vat_description = $get_vat_setup->vat_description;
                $dcontrol->vat_rate = $get_vat_setup->vat_rate;
                $dcontrol->vat_code = $pol_det['vat_charged'];
            }else{
                $dcontrol->vat_rate = 0;
            }
            $dcontrol->ira_rate = $pol_det['ira_rate'];
            $dcontrol->fronting_rate = $pol_det['fronting_rate'];
            $dcontrol->comm_rate = $pol_det['fronting_comm_rate'];
            $dcontrol->endt_renewal_no = (string) $new_endt_renewal_no;
            $dcontrol->dtrans_no = $dtran_no;
            $dcontrol->insured = (string) $insured[0]->name;
            $dcontrol->trans_type = $pol_det['type'];
            $dcontrol->dola = Carbon::now();
            $dcontrol->sum_insured = 0;
            $dcontrol->location = 0;
            $dcontrol->time = Carbon::now();
            $dcontrol->company_class_code = $pol_det['cls'];
            $dcontrol->account_year = $account_year;
            $dcontrol->account_month = $account_month;
            $dcontrol->name = trim($insured[0]->name);
            $dcontrol->cancelled = 'N';
            $dcontrol->reg_no = '';
            $dcontrol->source = 'U/W';
            $dcontrol->currency_rate = $currency_rate;
            $dcontrol->pin_no = 'Y'; 
            $dcontrol->client_number = (string) $insured[0]->client_number;
            $dcontrol->surname = (string) $insured[0]->surname;
            $dcontrol->others = (string) $insured[0]->others;
            $dcontrol->first_name = (string) $insured[0]->first_name;
            $dcontrol->client_type = $insured[0]->client_type;
            $dcontrol->incept_date = $pol_det['period_from'];
            $dcontrol->binder_flag = 'N';
            $dcontrol->line_no = 0;
            $dcontrol->renewal_date = $renewal;
            $dcontrol->expiry_date = $pol_det['period_to'];
            $dcontrol->risk_note_no = $pol_det['risk_note'];
            $dcontrol->external_pol_no = $pol_det['external_pol_number'];
            $dcontrol->fleet = 'N';
            $dcontrol->days_covered = $days_of_cover;
            $dcontrol->pvt_cover = '';
    
            $dcontrol->save();
    
            $policy_obj->add_polmaster((string) $new_endt_renewal_no, $uw_year);

            $resp = ['policy_no'=>$new_endt_renewal_no];
            $reg_risk = [];
            
            if ($motor_policy == 'Y') {
                /************save motor risk items************/
                $risk_det = $request->vehicles;
                foreach ($risk_det as $risk) {
                    $risk_motor = new MotorRiskController;
                    $motor_req = new NewMotorRequest($risk);
    
                    $motor_req->merge([
                        'endt_renewal_no' => $new_endt_renewal_no,
                        'trailer' => 'N',
                        'cls' => $cls->class
                    ]);
                    
                    $rules = $motor_req->rules();
                    $validator = Validator::make($motor_req->all(),$rules);
    
                    if($validator->fails()){
                        $resp = ['policy_no'=> ''];
                        return $this->errorResponse($validator->errors()->first());
                    }

                    $reg_risk = $risk_motor->save_vehicle($motor_req);

                    $resp['risk_details'] = $reg_risk['status'];

                }

            } else {
                # code...
            }
            DB::commit();

            $debit = new ExpressDebit;
            $debit_request = new Request;
            $debit_request->merge([
                'endt_renewal_no' => $new_endt_renewal_no,
                "interactive" => "N"
            ]);

            $debit_res = $debit->express_debit($debit_request);
            $resp['debit_status'] = $debit_res['debit_status'];

            if ($reg_risk['status'] == 1) {
                return $this->successResponse($resp,'Policy underwritten successfully', 201);
            }

        } catch (\Throwable $e) {
            DB::rollback();
            return $this->errorResponse($e->getMessage());
        }

    }

    /*********** validate client function***********/
    public function validateClient($client_no){
        $client_exists = Client::where('client_number', $client_no)->exists();

        if ($client_exists) {
            $resp = ['status' => 1];
        }else{
            $resp = ['status' => 0, 'message' => 'Client does not exist.'];
        }

        return $resp;
    }

    /*********** validate class function***********/
    public function validateClass($class){
        $class_exists = ClassModel::where('class', $class)->exists();
        
        if ($class_exists) {
            $resp = ['status' => 1];
        }else{
            $resp = ['status' => 0, 'message' => 'Class of business provided is invalid.'];
        }

        return $resp;
    }

    /*********** validate agent ***********/
    public function validateAgent($branch, $agent){
        $agent_exists = Agmnf::where('branch', $branch)->where('agent', $agent)->exists();

        if ($agent_exists) {
            $resp = ['status' => 1];
        }else{
            $resp = ['status' => 0, 'message' => 'Agent does not exist.'];
        }

        return $resp;
    }
}

?>