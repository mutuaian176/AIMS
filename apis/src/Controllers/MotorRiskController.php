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
use Crm\Apis\Rules\NewMotorRequest;
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
        int $seat_cap,
        string $cls,
        int $total_sum_insured = 0,
    )
    {
        $this->_cls = $cls;
        $this->_reg_no = $reg_no;
        $this->_endt_renewal_no = $endt_renewal_no;
        $this->_total_sum_insured = $total_sum_insured;
        $this->_seat_cap = $seat_cap;
    }

    public function save_vehicle(NewMotorRequest $request)
    {
        $motor_obj = new MotorProcessing;
        $cls = $request->cls;

        try {
            $motor_obj->pol_to_pta($request->endt_renewal_no);
            $dcontrol = Dcontrol::where('endt_renewal_no', $request->endt_renewal_no)->firstOrFail();
            $bustype = Bustype::where('type_of_bus', $dcontrol->type_of_bus)->first();

            $sum_insured = $total_sum_insured = (float) str_replace(',', '', $request->get('sum_insured'));
            $this->_total_sum_insured = $total_sum_insured;

            
            $motor_obj->setProps(
                endt_renewal_no: $dcontrol->endt_renewal_no,
                reg_no: $request->reg_no,
                cls : $request->cls,
                seat_cap : $request->seat_cap,
                total_sum_insured : $request->sum_insured
            );
            
            $transtype = $dcontrol->trans_type;
            
            $motor_obj->Save_motor_details($request);
            $motor_obj->update_motor_summary($dcontrol->endt_renewal_no,$cls,$request->reg_no);

            foreach ($request->premium_groups as $grp) {
                $risk_value = (float)str_replace(',','',$risk_values[$key]);
                $rate_amount = (float)str_replace(',','',$rate_amounts[$key]);

                $motorate = Motorsect::with('motprem_group')
                    ->where('class',$cls)
                    ->where('grp_code',$grp['premium_grp'])
                    ->where('item_code',$grp['section'])
                    ->firstOrFail();

                if($motorate->basis == 'R' && $motorate->rate_basis == 'S'){
                    $risk_value = $sum_insured;
                }

                $section = [
                    'group' => $grp['premium_grp'],
                    'item' => $grp['section'],
                    'rate_amount' => $grp['rate'],
                    'risk_value' => $risk_value,
                    'cancel' => 'N',
                ];
                
                $section['rate_amount'] = $motor_obj->get_minRateAmt($section);
                $premium_amounts = $motor_obj->compute_motor_premium($section,$dcontrol->ast_marker);
                $resp = $motor_obj->save_section_dtl($section,$premium_amounts);
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
  
            $motor_obj->update_motor_summary($dcontrol->endt_renewal_no,$cls,$request->reg_no);
            $motor_obj->update_polmaster($dcontrol->endt_renewal_no);

            return [
                'status' => 1,
                'message' => 'Motor details saved successfully'
            ];
            
        } catch (\Throwable $e) {
            throw $e;
        }

    }
}

?>