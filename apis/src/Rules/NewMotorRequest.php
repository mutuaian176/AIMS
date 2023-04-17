<?php

namespace Crm\Apis\Rules;

use App\Rules\RegNumberRule;
use App\Rules\ChasisNumberRule;
use App\Rules\EngineNumberRule;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\RequiredIf;

class NewMotorRequest extends FormRequest
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

        $rules =  [
            'trailer' => 'required',
            'reg_no' => 'required',
            'cls' => 'required',
            'cover_type' => 'required',
            'reg_no' => ['required', new RegNumberRule()],
            'v_make' => 'required',
            'v_model' => 'required',
            'body_type' => 'required',
            'premium_groups' => 'required'
        ];
        
        if ($this->motor_trade != 'Y') {
            $rules['chasis'] = ['required', new ChasisNumberRule()];
            $rules['engine'] = [Rule::requiredIf('trailer' == 'N'), new EngineNumberRule()];
            $rules['manufacture_yr'] = 'required';
            $rules['cc'] = Rule::requiredIf('trailer' == 'N');
            $rules['owner'] = 'required';
            $rules['motive_p'] = Rule::requiredIf('trailer' == 'N');
            // $rules['carry_cap'] = 'required';
            $rules['seat_cap'] = Rule::requiredIf('trailer' == 'N');
            $rules['met_color'] = 'required';
            $rules['color'] = 'required';
            $rules['condition'] = 'required';
        }

        if ($this->cover_type != 3) {
            $rules['sum_insured'] = 'required';
        }
        
        return $rules;
    }

    public function messages()
    {
        return [
            'cls.required' => 'Class is required',
            'group.required' => 'Premium is required',
            'section.required' => 'Premium section is required',
            'section.*.required' => 'Premium section is required',
            'rate_amount.*.required' => 'Rate/Amount field is required',
            'cover_type.required' => 'Cover type is required',
            'reg_no.required' => 'Registration number is required',
            'subclass.required' => 'Subclass is required',
            'chasis.required' => 'Chasis number is required',
            'owner.required' => 'Owner is required',
            'engine.required' => 'Engine number is required',
            'manufacture_yr.required' => 'Manufacture year is required',
            'v_make.required' => 'Vehicle make is required',
            'v_model.required' => 'Vehicle Model is required',
            'body_type.required' => 'Body type is required',
            'cc.required' => 'Cubic capacity is required',
            'motive_p.required' => 'Motive power is required',
            'carry_cap.required' => 'Carrying capacity is required',
            'seat_cap.required' => 'Seating capacity is required',
            'met_color.required' => 'Metallic color is required',
            'color.required' => 'Color is required',
            'sum_insured.required' => 'Sum insured is required',
            'condition.required' => 'Vehicle condition is required'
        ];
    }
}
