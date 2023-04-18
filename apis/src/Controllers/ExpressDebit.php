<?php
namespace Crm\Apis\Controllers;

use App\Dcontrol;
use App\Debitmast;
use App\Polmaster;
use App\ClassModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;



class ExpressDebit extends Controller{
    public function express_debit(Request $request)
    {
        DB::beginTransaction();
        try {
            $schem = schemaName();
            $gb = $schem['gb'];
            $gl = $schem['gl'];
            $common = $schem['common'];

            $endt_renewal_no = $request->get('endt_renewal_no');

            $dcontrol = Dcontrol::where('endt_renewal_no', $endt_renewal_no)->first();
            
            $polamster = Polmaster::where('endorse_no', $endt_renewal_no)->first();

            $result = array('status' => 0);

            $debited = Debitmast::where('endt_renewal_no', $endt_renewal_no)->count();

            $class = ClassModel::where('class', $dcontrol->class)->first();

            $workflow_id = $class->workflow_id;
            $pid = 5;
            

            $procedureName = '' . $gb . '.interactive_reinsure_precomput';
            $bindings = [
                'endt_renewal_no' => $endt_renewal_no,
                'grading' => 'N',
                'grade_down' => 'Z',
                'eml' => 100,
                'facultative_reice' => 0,
                'gfacultative_comm_reice' => 0,
                'per_location' => 'N',
                'glocation' => 0,
                'per_section' => 'N',
                'gsection' => 0,
                'do_debit' => 'Y',
                'per_combined'  => 'N',
                'gcombined' => $dcontrol->class,
                'final_reinsure'  => 'Y',
                'consolidate' => 'N',
                'w_effective_sum' => 0,
                'recon' => 'N',
                'g_user' => 'crm_api',
            ];

            $resp = DB::executeProcedure($procedureName, $bindings);

            if ($resp) {
                $result = ['debit_status' => 1];
            }

            DB::commit();
            
            return $result;
        }catch (\Throwable $e) {
            DB::rollback();
            dd($e);
            $error_msg = json_encode($e->getMessage());
            $reference = "Endt_renewal_no: {$endt_renewal_no}";
            $module = __METHOD__;
            $route_name = Route::getCurrentRoute()->getActionName();

            log_error_details($route_name,$error_msg,$reference,$module);

            
            $result = ['debit_status' => 0];
            return $result;
        }

    }
}