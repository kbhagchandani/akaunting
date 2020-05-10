<?php

namespace App\Http\Requests\Expense;

use App\Http\Requests\Request;

class BillAddItem extends Request
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
        return [
            'item_row' => 'required|integer',
            'currency_code' => 'required|string|currency',
        ];
    }
}
