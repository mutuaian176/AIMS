<?php

namespace Crm\Apis\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\gb\underwriting\Policy_details;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Client;
use App\Country;
use Carbon\Carbon;

class RegisterClientController extends Controller{
    public function index(Request $request){
        // DB::beginTransaction();
        try {
            $validated = Validator::make($request->all(),[
                // "name" => 'required',
                "client_type" => 'required',
                // "full_name" => 'required',
                // "pin_no" => 'required',
                // "email" => 'required',
                // "address_1" => 'required',
            ]);

            if ($validated->fails()) {
                 return response()->json([
                    'message' => 'Validation failed',
                    'error'  => $validated->errors(),
                 ]);
            }
            $client_gen = new Policy_details;
            $clnt_no=$client_gen->generate_client_number($request);
            
            $country_id = $request->country_code;
            $country_code = Country::where('id',$country_id)->value('country_code');
            $alpha3_country_code = Country::where('id',$country_id)->value('abbrev');

            $client=new Client;
            $client->client_number=$clnt_no;
            $client->address1=$request->address_1;
            $client->address2=$request->address_2;
            $client->address3=$request->address_3;
            $client->region=$request->region;
            $client->district=$request->district;
            $client->phy_loc=$request->street;
            $client->pin_number=$request->pin_no;
            $client->telephone=$request->phone_1;
            $client->mobile_no=$request->phone_2;
            // // $client->vrn=$request->vrn;
            $client->country_code=$country_code;
            $client->alpha3_country_code=$alpha3_country_code;
            $client->country_id= $country_id;
            $client->bank_account_no = $request->account_no[0];
            $client->bank_code = $request->bank_code[0];
            $client->branch_code = $request->branch[0];
            $client->contact_firstname=$request->contact_firstname;
            $client->contact_surname=$request->contact_surname;
            $client->contact_othername=$request->contact_othername;
            $client->contact_telephone=$request->contact_phone_no;
            $client->contact_position=$request->contact_position;
            $client->group_code=$clnt_no;
            $client->dola=Carbon::today();
            $client->client_type=$request->client_type;
            $client->e_mail=$request->email;
            $client->crm_flag = 'Y';
            //$client->vat_exempt=$request->vat_exempt;
            //$client->user_str=Auth::user()->user_name;
            
            switch ($request->client_type)
            {
                case 'I':   
                        $client->title=$request->salutation_code;
                        $client->occupation=$request->occupation_code;
                        $client->first_name=$request->fname;
                        $client->others=$request->other_names;
                        $client->surname=$request->surname;
                        $client->name=$request->fname.' '.$request->other_names.' '.$request->surname;
                        $client->gender=$request->gender;
                        $client->dob=$request->date_of_birth;
                        $client->identity_type=$request->id_type;
                        if ($request->id_type = 'P') {
                            $client->passport_number=$request->identity_no;
                        }else{
                            $client->id_number=$request->identity_no;
                        }

                        $cnt_id = Client::where('id_number',$request->identity_no)
                                        ->where('identity_type', $request->id_type)
                                        ->whereNotNull('id_number')->count();
                        
                        if($cnt_id > 0){
                            return response()->json([
                                'message' => 'Client with that Identity already exists'
                            ]);
                        }

                    break;
                
                case 'C':
                        $client->name=$request->corporate_name;
                        $client->incorporation_cert=$request->incorporation_cert;
                        $client->identity_type='C';
                        // $client->id_number=$request->incorporation_cert;
                        $pinno = Client::where('pin_number',$request->pin_no)
                                        ->whereNotNull('pin_number')->count();

                        if($pinno>0)
                        {
                            return response()->json([
                                'message' => 'Client with that Pin already exists'
                            ]);
                        }

                    break;
            }

            $client->save();

            
            $morebanks = count($request->bank_code);
            if ($morebanks > 1) {
                for($i=1; $i <= $morebanks; $i++ ){
                    if($request->bank_code[$i] != null){
                        $clientbank = new Clientbanks;
                        $clientbank->client_number = $clnt_no;
                        $clientbank->item_no = $i + 1;
                        $clientbank->bank_code = $request->bank_code[$i];
                        $clientbank->branch_code = $request->branch[$i];
                        $clientbank->bank_account_name = $request->account_name[$i];
                        $clientbank->bank_account_no = $request->account_no[$i];
                        $clientbank->bank_account = $request->account_no[$i];
                        $clientbank->in_use = 'Y';
                        $clientbank->holder = 'CLIENT';
                        $clientbank->save();
                    }
                }
            }


            return response()->json([
                'message' => 'Client integrated successfully',
                'client_no' => $clnt_no
            ]);

        }
        catch(\Throwable $e){
            return response()->json([
                'message' => 'Failed',
                'errors' => $e
            ]);
        }

            

    }
}

?>