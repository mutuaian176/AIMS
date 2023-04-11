<?php
namespace Crm\Apis\Controllers;

use DateTime;
use App\Tran0;
use Exception;
use App\Client;
use App\Dtran0;
use App\Bustype;
use App\Classbr;
use App\Country;
use App\Curr_ate;
use App\Currency;
use App\Dcontrol;
use App\Polmaster;
use App\Transtype;
use Carbon\Carbon;
use App\ClassModel;
use App\Models\Modtlmast;
use App\Models\Motcvrdet;
use App\Models\Modtlpivot;
use App\Models\Motorsect; 
use App\Models\Motorpolsec;
use Illuminate\Http\Request;
use App\Models\Motorprem_grp;
use Crm\Apis\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\NewMotorRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\gb\underwriting\Risk;
use App\Http\Controllers\gb\underwriting\Policy;
use App\Http\Controllers\gb\underwriting\Policy_details;
use App\Http\Controllers\gb\underwriting\MotorProcessing;


class MotorRiskController extends Controller{
    use ApiResponse;

    private $_total_sum_insured;
    private $_cls;
    private $_reg_no;
    private $_endt_renewal_no;
    private $_seat_cap;
    private $_preview;

    public function setProps(
        string $endt_renewal_no,
        string $reg_no,
        string $cls,
        int $total_sum_insured = 0,
    )
    {
        $this->_cls = $cls;
        $this->_reg_no = $reg_no;
        $this->_endt_renewal_no = $endt_renewal_no;
        $this->_total_sum_insured = $total_sum_insured;
    }

    public function save_vehicle(NewMotorRequest $request)
    {
        // dd($request->all());
        $motor_obj = new MotorProcessing;
        $groups = $request->groups;
        $sections = $request->sections;
        $rate_amounts = $request->rate_amounts;
        $risk_values = $request->risk_values;
        $cls = $request->cls;
        // return $cls;

        DB::beginTransaction();
        try {
            $motor_obj->pol_to_pta($request->endt_renewal_no);
            $dcontrol = Dcontrol::where('endt_renewal_no', $request->endt_renewal_no)->firstOrFail();
            $bustype = Bustype::where('type_of_bus', $dcontrol->type_of_bus)->first();

            $sum_insured = $total_sum_insured = (float) str_replace(',', '', $request->get('sum_insured'));
            $this->_total_sum_insured = $total_sum_insured;
            
            $transtype = $dcontrol->trans_type;
            $this->_cls = $request->cls;
            $this->_reg_no = $request->reg_no;
            $this->_endt_renewal_no = $request->endt_renewal_no;
            $this->_seat_cap = $request->insured_seats;
            
            $motor_obj->Save_motor_details($request);
            $motor_obj->update_motor_summary($dcontrol->endt_renewal_no,$cls,$request->reg_no);

            foreach ($groups as $key => $grp) {
                $risk_value = (float)str_replace(',','',$risk_values[$key]);
                $rate_amount = (float)str_replace(',','',$rate_amounts[$key]);

                $motorate = Motorsect::with('motprem_group')
                    ->where('class',$cls)
                    ->where('grp_code',$grp)
                    ->where('item_code',$sections[$key])
                    ->firstOrFail();

                if($motorate->basis == 'R' && $motorate->rate_basis == 'S'){
                    $risk_value = $sum_insured;
                }

                $section = [
                    'group' => $grp,
                    'item' => $sections[$key],
                    'rate_amount' => $rate_amount,
                    'risk_value' => $risk_value,
                    'cancel' => 'N',
                ];
                
                $section['rate_amount'] = $this->get_minRateAmt($section);
                $premium_amounts = $this->compute_motor_premium($section,$dcontrol->ast_marker);
                $resp = $this->save_section_dtl($section,$premium_amounts);
                if($dcontrol->trans_type == 'PTA'){
                    $motor_obj->saveComesaDetails($request,$request->endt_renewal_no,$request->reg_no);
                }

                if($resp['status'] == 1){
                    continue;
                }
                else{
                    break;
                    throw new Exception($resp['message'],500);
                    
                }
            }
            // return ['endt'=>$dcontrol->endt_renewal_no];
  
            $motor_obj->update_motor_summary($dcontrol->endt_renewal_no,$cls,$request->reg_no);
            $motor_obj->update_polmaster($dcontrol->endt_renewal_no);

            DB::commit();
            return [
                'status' => 1,
                'endt'=>$dcontrol->endt_renewal_no,
                'message' => 'Motor details saved successfully'
            ];
        } catch (\Throwable $e) {
            DB::rollback();
            dd($e);
            $error_msg = json_encode($e->getMessage());
			$reference = "Endorsement_no: {$request->endt_renewal_no}";
            $module = __METHOD__;
			$route_name = Route::getCurrentRoute()->getActionName();

			log_error_details($route_name,$error_msg,$reference,$module);

            return response()-> json([
                'status' => 0,
                'endt'=>$dcontrol->endt_renewal_no,
                'message' => 'Failed to process motor',
                
            ],500);
        }
        
    }

    public function get_minRateAmt($section) : float
    {
        $cls = $this->_cls;
        $dcontrol = Dcontrol::where('endt_renewal_no', $this->_endt_renewal_no)->firstOrFail();
        $motorate = Motorsect::where('class',$cls)
            ->where('grp_code',$section['group'])
            ->where('item_code',$section['item'])
            ->firstOrFail();

        $isBasic = Motorprem_grp::where('grp_code',$section['group'])
            ->where('basic_premium','Y')
            ->exists();
        $rate_amount = $section['rate_amount'];

        switch($motorate->basis){
            case 'R':
                // check basic

                $binder = (new Risk)->getBinderRate($dcontrol);
                
                $rate_amount = $section['rate_amount'];
                if($isBasic && $binder->rate > 0){
                    $rate_amount = $binder->rate;
                }
                else if ((float)$section['rate_amount'] < $motorate->min_rate_amt && $motorate->min_rate_amt != 0 ) {
                    $rate_amount = $motorate->min_rate_amt;
                }

                if($rate_amount > 100){
                    throw new Exception("Rate cannot exceed 100",403);
                    
                }
                break;
            default:
                if ((float)$section['rate_amount'] < ($motorate->min_rate_amt * $dcontrol->currency_rate) && $motorate->min_rate_amt !=0) {
                    $rate_amount = $motorate->min_rate_amt;
                }
                break;
        }

        return $rate_amount;

    }

    public function compute_motor_premium($section,$method)
    {
        // normalize data
        $risk_value = (float) str_replace(',', '', $section['risk_value']);
        $rate_amount = (float) str_replace(',', '', $section['rate_amount']);

        // globals
        $cls = $this->_cls;
        $rate = 0;
        $annual_premium = 0;
        $premium_movt = 0;
        $risk_value_movt = 0;
        $endorse_amount = 0;
        $cancelNewSection = false;

        $dcontrol = Dcontrol::where('endt_renewal_no', $this->_endt_renewal_no)->firstOrFail();
        $polmaster = Polmaster::where('endorse_no', $this->_endt_renewal_no)->firstOrFail();
        $modtl = Modtlmast::where('policy_no',$dcontrol->policy_no)
            ->where('reg_no',$this->_reg_no)
            ->first();
        $dcontrolPrev = Dcontrol::previous_endorsement($dcontrol->endt_renewal_no);
        $motorate = Motorsect::with('motprem_group')
            ->where('class',$cls)
            ->where('grp_code',$section['group'])
            ->where('item_code',$section['item'])
            ->firstOrFail();

        // check if vehicle has been reinstated in this endorsement
        $reinstated = Modtlpivot::reinstatement_status($dcontrol->endt_renewal_no,$this->_reg_no);
        
        // if reinstated, don't pick previous section details. Treat like POL/REN/RNS
        switch ($reinstated) {
            case false:
                $prevSect = Motcvrdet::where('policy_no',$dcontrol->policy_no)
                    ->where('endt_renewal_no',$dcontrolPrev->endt_renewal_no)
                    ->where('reg_no',$this->_reg_no)
                    ->where('grp_code',$section['group'])
                    ->where('item_code',$section['item'])
                    ->first();
            break;
            default:
                $prevSect = null;
        }

        $prev_annual = $prevSect->annual_premium ?? 0;
        $prev_risk_value = $prevSect->risk_value ?? 0;
        $free_limit = (float)$motorate->free_limit ?? 0;
        $base = $motorate->base;

        $basicPremium = Motorpolsec::basic_premium($dcontrol->endt_renewal_no,$this->_reg_no);
        if($this->_preview == 'Y' && $basicPremium == null){
            $basicPremium = Cache::get($this->_endt_renewal_no);
        }

        switch($motorate->basis){
            case 'R':
                // check if risk value is negative
                if($risk_value< 0){
                    throw new Exception("Risk Value cannot be less than 0",403);
                }
                elseif ((float)$basicPremium == 0 && $motorate->rate_basis == 'P') {
                    throw new Exception("Basic Premium is zero. Principal is the Basic Premium",403);
                }

                if($free_limit >= $risk_value){
                    $actual_risk_value = 0;
                }
                elseif($section['cancel'] == 'Y'){
                    $actual_risk_value = $risk_value;
                }
                else{
                    switch ($motorate->rate_basis) {
                        case 'S':
                        case 'F':
                            $actual_risk_value = $risk_value - $free_limit;
                        break;
                        case 'P':
                            $actual_risk_value = $basicPremium - $free_limit;
                        break;
                        default:
                            # code...
                            break;
                    }
                }

                $rate = $rate_amount;
                // sum insured
                if($motorate->rate_basis == 'S'){
                    $annual_premium = ($rate * ($actual_risk_value))/$base;
                }
                // basic premium
                else if($motorate->rate_basis == 'P'){
                    $annual_premium = ($rate * ($actual_risk_value))/$base;
                }
                else if($motorate->rate_basis == 'F'){
                    $annual_premium = ($rate * ($actual_risk_value))/$base; 
                }
                break;
            case 'A':
                $annual_premium = (float)$rate_amount;
            default: 
                break;
        }

        // check if per seat premium applies
        if($motorate->per_carry == 'Y'){
            if(isset($this->_seat_cap)){
                $seat_cap = (int)$this->_seat_cap;
            }else{
                $seat_cap = (int)$modtl->seat_cap;
            }

            $perSeat_premium = ($seat_cap) * (float)$motorate->per_carry_std;
            $annual_premium += $perSeat_premium;
        }

        // CHECK IF annual premium IS LESS THAN MINIMUM PREMIUM
        $minimum_prem = (float)($motorate->minimum_premium/$dcontrol->currency_rate);
        if($minimum_prem > (float)$annual_premium ){
            $annual_premium = $minimum_prem;
        }     
        
        switch ($dcontrol->trans_type) {
            case 'EXT':
            case 'RFN':
            case 'CXT':
                // section existed in previous transaction
                if(isset($prevSect) && $section['cancel'] != 'Y'){
                    $premium_movt = (float)$annual_premium - $prev_annual;
                    $risk_value_movt = (float)$risk_value - $prev_risk_value;
                }
                else{
                    $premium_movt = $annual_premium;
                    $risk_value_movt = $risk_value;
                }
            break;
            default:
                $premium_movt = $annual_premium;
                $risk_value_movt = $risk_value;
            break;
        }

        // cancellation but section didn't exist in previous endorsement or POL, REN,RNS
        if($section['cancel'] == 'Y'){
            switch ($dcontrol->trans_type) {
                case 'EXT':
                case 'RFN':
                case 'CXT':
                    if(empty($prevSect)){
                        $cancelNewSection = true;
                    }
                    break;
                default:
                    $cancelNewSection = true;
                    break;
            }
        }
        // No refund for new sections/vehicle
        if($cancelNewSection){
            $endorse_amount = 0;
        }
        elseif($motorate->motprem_group->proratable == 'Y'){
            switch ($method) {
                case 'A':
                    $endorse_amount = (new Risk)->prorate($dcontrol->endt_renewal_no, $premium_movt);
                    break;
                case 'S':
                    // percentage
                    $yearLength = (new Risk)->yearLength($polmaster->uw_year, $dcontrol->endt_renewal_no);
                    if($dcontrol->short_term_method == 'S'){
                        $endorse_amount = $premium_movt * $dcontrol->short_term_percent / 100;
                    }
                    else{
                        $endorse_amount = $premium_movt * ($dcontrol->days_covered / $yearLength);
                    }
                    break;
                case 'T':
                    $endorse_amount = $premium_movt * ($dcontrol->endt_days / $dcontrol->days_covered);
                    break;
                
                default:
                    
                    break;
            }
        }
        else{
            $endorse_amount = $premium_movt;
        }

        if($section['cancel'] == 'Y'){
            $endorse_amount *=-1; 
        }

        return [
            'annual_premium' => $annual_premium,
            'premium_movt' => $premium_movt,
            'risk_value_movt' => $risk_value_movt,
            'endorse_amount' => $endorse_amount
        ];
    }

    public function save_section_dtl($section,$premiumAmts)
    {
        $cls = $this->_cls;
        $dcontrol = Dcontrol::where('endt_renewal_no', $this->_endt_renewal_no)->firstOrFail();
        $motorate = Motorsect::where('class',$cls)
            ->where('grp_code',$section['group'])
            ->where('item_code',$section['item'])
            ->firstOrFail();

        $sectionAnnual = Motcvrdet::where('policy_no',$dcontrol->policy_no)
            ->where('endt_renewal_no',$dcontrol->endt_renewal_no)
            ->where('reg_no',$this->_reg_no)
            ->where('grp_code',$section['group'])
            ->where('item_code',$section['item'])
            ->first();

        $trans_section = Motorpolsec::where('policy_no',$dcontrol->policy_no)
            ->where('endt_renewal_no',$dcontrol->endt_renewal_no)
            ->where('reg_no',$this->_reg_no)
            ->where('grp_code',$section['group'])
            ->where('item_code',$section['item'])
            ->first();

        // global
        $deleteSection = false;
        $rate = $motorate->basis == 'R' ? $section['rate_amount'] : 0;
        $risk_value = (float) $section['risk_value'];

        if(isset($sectionAnnual)){
            Motcvrdet::where('policy_no',$dcontrol->policy_no)
                ->where('endt_renewal_no',$dcontrol->endt_renewal_no)
                ->where('reg_no',$this->_reg_no)
                ->where('grp_code',$section['group'])
                ->where('item_code',$section['item'])
                ->update([
                    'risk_value' => $risk_value,
                    'rate' => $rate,
                    'annual_premium' => $premiumAmts['annual_premium'],
                    'updated_by' => Auth::user()->user_name,
                    'cancelled' => $section['cancel'],
                ]);
        }
        else{
            $motcvrdet = new Motcvrdet();
            $motcvrdet->policy_no = $dcontrol->policy_no;
            $motcvrdet->endt_renewal_no = $dcontrol->endt_renewal_no;
            $motcvrdet->class = $cls;
            $motcvrdet->transeq_no = $dcontrol->transeq_no;
            $motcvrdet->reg_no = $this->_reg_no;
            $motcvrdet->rate = $rate;
            $motcvrdet->grp_code = $section['group'];
            $motcvrdet->item_code = $section['item'];
            $motcvrdet->risk_value = $risk_value;
            $motcvrdet->annual_premium = $premiumAmts['annual_premium'];
            $motcvrdet->created_by = Auth::user()->user_name;
            $motcvrdet->cancelled = $section['cancel'];
            $motcvrdet->save();
        }
        
        switch ($dcontrol->trans_type) {
            case 'EXT':
            case 'RFN':
            case 'CXT':
                $dcontrolPrev = Dcontrol::previous_endorsement($dcontrol->endt_renewal_no);  
                $prevTrans_sect = Motorpolsec::where('policy_no',$dcontrol->policy_no)
                    ->where('endt_renewal_no',$dcontrolPrev->endt_renewal_no)
                    ->where('reg_no',$this->_reg_no)
                    ->where('grp_code',$section['group'])
                    ->where('item_code',$section['item'])
                    ->first();

                // remove item if no change has happened
                if(isset($prevTrans_sect) && $premiumAmts['endorse_amount'] ==0){

                    $deleteSection = true;
                }
                break;
            
            default:
                if($section['cancel'] == 'Y' && $premiumAmts['endorse_amount'] ==0){
                    $deleteSection = true;
                }
                break;
        }
        
        if($deleteSection){
            
            $deletedSection =Motorpolsec::where('policy_no',$dcontrol->policy_no)
                ->where('endt_renewal_no',$dcontrol->endt_renewal_no)
                ->where('reg_no',$this->_reg_no)
                ->where('grp_code',$section['group'])
                ->where('item_code',$section['item'])
                ->delete();
        }
        else if(isset($trans_section)){

            Motorpolsec::where('policy_no',$dcontrol->policy_no)
                ->where('endt_renewal_no',$dcontrol->endt_renewal_no)
                ->where('reg_no',$this->_reg_no)
                ->where('grp_code',$section['group'])
                ->where('item_code',$section['item'])
                ->update([
                    'risk_value' => $risk_value,
                    'risk_value_movt' => $premiumAmts['risk_value_movt'],
                    'rate' => $rate,
                    'annual_premium' => $premiumAmts['annual_premium'],
                    'premium_movt' => $premiumAmts['premium_movt'],
                    'endorse_amount' => $premiumAmts['endorse_amount'],
                    'updated_by' => Auth::user()->user_name
                ]);

        }
        else{
            $motorpolsec = new Motorpolsec();
            $motorpolsec->policy_no = $dcontrol->policy_no;
            $motorpolsec->endt_renewal_no = $dcontrol->endt_renewal_no;
            $motorpolsec->class = $cls;
            $motorpolsec->transeq_no = $dcontrol->transeq_no;
            $motorpolsec->reg_no = $this->_reg_no;
            $motorpolsec->grp_code = $section['group'];
            $motorpolsec->item_code = $section['item'];
            $motorpolsec->risk_value = $risk_value;
            $motorpolsec->risk_value_movt = $premiumAmts['risk_value_movt'];
            $motorpolsec->rate = $rate;
            $motorpolsec->annual_premium = $premiumAmts['annual_premium'];
            $motorpolsec->premium_movt = $premiumAmts['premium_movt'];
            $motorpolsec->endorse_amount = $premiumAmts['endorse_amount'];
            $motorpolsec->created_by = Auth::user()->user_name;
            $motorpolsec->save();
        }
        
        return [
            'status' => 1,
            'message' => 'Persisted to database'
        ];
    }
}

?>