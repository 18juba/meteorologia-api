<?php

namespace App\Validations;

use App\Models\User;
use Illuminate\Http\Request;

class UserValidation extends Validation
{
    public static function store(Request $data)
    {
        if (!$data->nome) {
            return self::response(400, 'É obrigatório enviar o nome');
        }

        if (!$data->senha) {
            return self::response(400, 'É obrigatório enviar a senha');
        }

        if (!$data->email) {
            return self::response(400, 'É obrigatório enviar o email');
        }

        if (User::where('email', $data->email)->exists())
        {
            return self::response(400, 'Já existe um usuário com esse email');
        }
    }
}
