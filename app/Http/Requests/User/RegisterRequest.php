<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {

        //poderiamos ser mais exigentes quanto a validação dos parametros. ex:
        /*
        'user_name' => 'required|string|min:2|max:100|regex:/^[a-zA-ZÀ-ÿ\s]+$/', definir melhores critérios para nomes de usuario
        'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', definir parâmetros mais incisivos para criação de senha
        */
        return [
            'user_document_number'    => 'required|regex:/[0-9]{11}/i',
            'user_name'               => 'required',
            'company_document_number' => 'required|regex:/[0-9]{14}/i',
            'company_name'            => 'required',
            'email'                   => 'required|email',
            'password'                => 'required',
        ];
    }
}
