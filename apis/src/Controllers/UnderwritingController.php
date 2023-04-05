<?php
namespace Crm\Apis\Controllers;

use DateTime;
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
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\gb\underwriting\Policy;
use App\Http\Controllers\gb\underwriting\Policy_details;


class UnderwritingController extends Controller{
    use apiResponse;
    public function generatePolicy(Request $request){
        DB::beginTransaction();
        try {
            $cls = ClassModel::where('class', $request->class)->first();
            // return $this->successResponse(['class' => $cls],'Client integrated successfully', 201);
                    
            $uw_year = date('Y', strtotime((string) $request->period_from));
    
            if($cls->exem == "Y"){
                $renewal = new DateTime((string) $request->period_to);
                $renewal->modify('+1 day');
            }
            else{
                $renewal = new DateTime((string) $request->period_to);
            }
    
            /*dcon_no */
            $transaction_no = Tran0::where('rec_no', 0)->get(['tran_no']);
            $tran_no = $transaction_no[0]->tran_no;
            $tran0 = Tran0::where('rec_no', 0)->increment('tran_no', (int) '1');
            
            /* get dtrans_no and period */
            $doc_trans = Dtran0::where('rec_no', 0)->get(['dtran_no', 'account_month', 'account_year']);
            $dtran_no = $doc_trans[0]->dtran_no;
            $account_month = $doc_trans[0]->account_month;
            $account_year = $doc_trans[0]->account_year;
            $dtran0 = Dtran0::where('rec_no', 0)->increment('dtran_no', (int) '1');
            
            $policy_obj = new Policy();
            $new_endt_renewal_no = $policy_obj->generate_pol(
                $request->branchpol,
                $request->class,
                $request->type,
                $account_year,
                $account_month
            );
    
            /*get currency*/
            $currency = $request->currency; //Currency::where('base_currency','Y')->get(['currency_code']);
            $currency_code = $currency; //$currency[0]->currency_code;

            /*get current currency rate*/
            $currency_rate = 1;
            // $currency_rate = $policy_obj->get_todays_rate($currency_code);
    
            $date = Carbon::today(); 
            $currency = Currency::where('currency_code', $currency_code)->get(['currency', 'base_currency']);
            $currency = $currency[0];
    
    
    
            if($currency->base_currency == 'Y') {
                $currency_rate = 1;
            }else {
                $count_curr = Curr_ate::where('currency_code', $currency_code)
                    ->where('rate_date', $date)
                    ->count();
    
    
                if ($count_curr > 0) {
                    $rate = Curr_ate::where('currency_code', $currency_code)
                        ->where('rate_date', $date)
                        ->get();
    
                    $currency_rate = $rate[0]->currency_rate;
                } else {
                    // $req = new Request;
                    $req = request()->merge(['currency' => $currency_code]);
                    $currency_rate = $policy_obj->yesterdayRate($req);
                }
            }
    
            // /*get class department*/
            $class = ClassModel::where('class', $request->class)->first();
            $dept = (string) $class->dept;
    
            /*get number of days of cover*/
            $cover_from = Carbon::parse($request->effective_date);
            $cover_to = Carbon::parse($request->period_to);
    
            $days_of_cover = $cover_from->diffInDays($cover_to);
    
    
            $prop_number = Classbr::where('class', $request->class)->get(['prop_serial']);
            $prop_no = $prop_number[0]->prop_serial;
            $dprop_no = Classbr::where('class', $request->class);
    
            $dprop_no = Classbr::where('class', $request->class)->increment('prop_serial', (int) '1');
    
    
            $seq_no = Dcontrol::generateTranseqNumber($request->type,$polno->policy_no);
            /*insured array*/
    
            $insured = Client::where('client_number', $request->client_no)->get(['name', 'client_number', 'client_type']);
    
            /*get doc type */
            $document_type = Transtype::where('descr', $request->type)->get(['doc_type']);
            $doc_type = $document_type[0]->doc_type;
    
            //type of bus
            $bustype_curr = Bustype::where('type_of_bus', trim($request->bustype))->get();
            $bustype_curr = $bustype_curr[0];
    
            //add new record to dcontrol
            $dcontrol = new Dcontrol;
            $dcontrol->dcon_no = $tran_no;
            $dcontrol->transeq_no = $seq_no;
            $dcontrol->doc_type = strtoupper($doc_type);
            
            $dcontrol->dprop_no=$prop_no;
            $dcontrol->policy_no=(string)$new_endt_renewal_no;
            $dcontrol->prop_date=Carbon::now();
            $dcontrol->branch=$request->branchpol;
            $dcontrol->agent=$request->agentpol;
            $dcontrol->class=$request->class;
            $dcontrol->user_str='crm';
    
            $dcontrol->period_from=$request->period_from;
            $dcontrol->period_to=$request->period_to;
            $dcontrol->old_policy_no = $request->old_pol;
            $dcontrol->renew_old_policy = 'N';
    
            $dcontrol->doc_type = 'DRN';
    
            if(trim($request->ast) == 'T'){
                $dcontrol->period_from = $request->t_period_from;
                $dcontrol->period_to =$request->t_period_to;
                $dcontrol->cov_period_from = $request->period_from;
                $dcontrol->cov_period_to = $request->period_to;
                $dcontrol->endt_days = $request->t_cover_days;
            }
            else if(trim($request->ast) == 'S' && trim($request->prem_method) == 'S'){
                $dcontrol->period_from=$request->t_period_from;
                $dcontrol->period_to=$request->t_period_to;
                $dcontrol->cov_period_from=$request->period_from;
                $dcontrol->cov_period_to=$request->period_to;
                $dcontrol->endt_days = $request->t_cover_days;
                
                $shortTermRate = $ $policy_obj->verifyShortTermRate($request->class,$request->t_cover_days);
    
            }else{
                $dcontrol->period_from=$request->period_from;
                $dcontrol->period_to=$request->period_to;
                $dcontrol->cov_period_from=$request->period_from;
                $dcontrol->cov_period_to=$request->period_to;
            }
    
            $dcontrol->short_term_method=$request->prem_method;
            $dcontrol->short_term_percent=$shortTermRate;
            
            $dcontrol->effective_date=$request->period_from;
            $dcontrol->branch_code= str_pad($request->branchpol, 3,"0",STR_PAD_LEFT);
            $dcontrol->co_insure=$request->co_ins;
    
            $dcontrol->ext_from = $dcontrol->period_from;
            $dcontrol->ext_to = $dcontrol->period_to;
    
            if($dcontrol->co_insure=='Y' && $pipcnam->coins_rate_per_sec!='Y'){
                $dcontrol->co_ins_rate=$request->co_ins_rate;
                $dcontrol->co_ins_base=$request->co_ins_base;
            }else{
                $dcontrol->co_ins_rate=0;
                $dcontrol->co_ins_base=0;
            }
            
            $dcontrol->type_of_bus=$request->bustype;
            $dcontrol->dept=$dept;
            $dcontrol->actual_period_from=$request->period_from;
            $dcontrol->actual_period_to=$request->period_to;
            $dcontrol->financed=$request->financed;
            $dcontrol->ipf=$request->ipf;
            $dcontrol->ast_marker=$request->ast;  
            $dcontrol->items_total=1;
            $dcontrol->branch_cod=$request->branchpol;
            $dcontrol->currency=$currency_code;
            $dcontrol->ipf_repayment_date=$request->ipf_repayment_date;
    
            if($request->financed=='Y'){
    
                $dcontrol->financed_code=$request->financier;
            }else{
                $dcontrol->financed_code='';
            }
    
            if ($request->co_ins == 'Y') {
    
                $dcontrol->company_share = $request->co_ins_share;
            } else {
                $dcontrol->company_share = 100;  //must be 100 for debit and reinsurances to work if no co insurance is done
            }
    
            if($charge_vat == 'Y'){
                $dcontrol->vat_type = $get_vat_setup->vat_type;
                $dcontrol->vat_description = $get_vat_setup->vat_description;
                $dcontrol->vat_rate = $get_vat_setup->vat_rate;
                $dcontrol->vat_code = $request->vat_charged;
            }else{
                $dcontrol->vat_rate = 0;
            }
            $dcontrol->ira_rate = $request->ira_rate;
            $dcontrol->fronting_rate = $request->fronting_rate;
            $dcontrol->comm_rate = $request->fronting_comm_rate;
    
            $dcontrol->endt_renewal_no = (string) $new_endt_renewal_no;
            $dcontrol->dtrans_no = $dtran_no;
            $dcontrol->insured = (string) $insured[0]->name;
            $dcontrol->trans_type = $request->type;
            $dcontrol->dola = Carbon::now();
            $dcontrol->sum_insured = 0;
            $dcontrol->location = 0;
            $dcontrol->time = Carbon::now();

            $dcontrol->company_class_code = $request->class;
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
            $dcontrol->incept_date = $request->period_from;
    
            $dcontrol->binder_flag = 'N';
            $dcontrol->line_no = 0;
            $dcontrol->renewal_date = $renewal;
    
            $dcontrol->risk_note_no = $request->risk_note;
            $dcontrol->external_pol_no = $request->external_pol_number;
            $dcontrol->fleet = 'N';
            $dcontrol->days_covered = $days_of_cover;
            $dcontrol->pvt_cover = '';
    
            $dcontrol->save();
    
            $policy_obj->add_polmaster((string) $new_endt_renewal_no, $uw_year);

            $resp = ['policy_no'=>$new_endt_renewal_no];

            DB::commit();
            
            return $this->successResponse($resp,'Policy underwritten successfully', 201);

        } catch (\Throwable $e) {
            dd($e);
            DB::rollback();
            return $this->errorResponse($e);
        }

    }
}

?>