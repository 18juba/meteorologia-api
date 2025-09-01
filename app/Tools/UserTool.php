<?php

namespace App\Tools;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Facades\Tool;

class UserTool
{
    public static function UserData()
    {
        $user = Auth::user();
        $localizacao = json_decode($user->localizacao, true);

        $city = $localizacao['address']['city'] ?? 'Cidade não informada';
        $state = $localizacao['address']['state'] ?? 'Estado não informado';
        $displayName = $localizacao['display_name'] ?? 'Localização desconhecida';

        $hobbies = "Futebol, programação"; // Implementar vindo do user depois

        $userTool = Tool::as('user')
            ->for('Buscar as informações do usuário contendo localização e hobbies')
            ->withStringParameter('city', 'Cidade do usuário')
            ->withStringParameter('state', 'Estado do usuário')
            ->withStringParameter('hobbies', 'Hobbies ou interesses do usuário')
            ->using(function () use ($user, $city, $state, $hobbies, $displayName): string {
                return json_encode([
                    'id' => $user->id,
                    'nome' => $user->nome,
                    'email' => $user->email,
                    'cidade' => $city,
                    'estado' => $state,
                    'localizacao_legivel' => $displayName,
                    'hobbies' => $hobbies,
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            });

        return $userTool;
    }
}
