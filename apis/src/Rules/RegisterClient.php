<?php

namespace Crm\Apis\Rules;

use Illuminate\Foundation\Http\FormRequest;

class RegisterClient extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [
            'client_type' => 'required',
            'email' => 'required|email',
            'pin_number' => 'required|unique:client',
            'country_code' => 'required',
            'mobile_no' => 'required',
            'occupation' => 'required',
            'address'=>'required',
            'name'=>'required',

        ];

        if ($this->client_type == 'I') {
            $rules['date_of_birth'] = 'required';
            $rules['id_type'] = 'required';
            $rules['id_number'] = 'required|unique:client';
        }else{
            $rules['incorporation_cert'] = 'required|unique:client';
            $rules['telephone'] = 'required|unique:client';
        }
        return $rules;

    }
}
