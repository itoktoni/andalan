<?php

namespace App\Http\Requests;

use App\Dao\Models\Rs;
use App\Dao\Traits\ValidationTrait;
use Illuminate\Foundation\Http\FormRequest;

class RsRequest extends FormRequest
{
    use ValidationTrait;

    public function validation(): array
    {
        return [
            Rs::field_name() => 'required',
            // Rs::field_harga_cuci() => 'required|numeric',
            // Rs::field_harga_sewa() => 'required|numeric',
            'rs_code' => 'required|alpha:ascii|unique:rs,rs_code|min:3|max:3',
            'rs_status' => 'required',
            'ruangan' => 'required',
            'jenis' => 'required',
        ];
    }

    public function prepareForValidation(){

        $this->merge([
            Rs::field_active() => 1,
            Rs::field_code() => strtoupper($this->rs_code)
        ]);
    }
}
