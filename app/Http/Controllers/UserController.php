<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AIService;
use App\Services\WeatherService;
use App\Traits\WeatherTrait;
use App\Validations\UserValidation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use WeatherTrait;

    public function registro(Request $request)
    {
        if ($validate_errors = UserValidation::store($request)) {
            return $validate_errors;
        }

        $user = User::create([
            'nome'  => $request->nome,
            'senha' => Hash::make($request->senha),
            'email' => $request->email
        ]);

        return response()->json([
            'status' => [
                'code'      => 201,
                'message'   => 'Conta criada com sucesso'
            ],
            'user' => $user
        ], 201);
    }

    public function update(Request $request)
    {
        $user = User::where('id', Auth::id())->first();

        $data = $request->validate([
            'hobbies'   => 'sometimes|string|max:5000',
        ]);

        $user->update($data);

        return response()->json([
            'status' => [
                'code'      => 200,
                'message'   => 'Perfil atualizado com sucesso'
            ],
            'user' => $user
        ], 200);
    }

    public function atualizar_endereco(Request $request)
    {
        $latitude   = $request->input('latitude');
        $longitude  = $request->input('longitude');

        if (! $latitude || ! $longitude) {
            return response()->json([
                'status' => [
                    'code'    => 400,
                    'message' => 'Latitude e longitude são obrigatórias'
                ]
            ], 400);
        }

        $response = $this->reverse_search($latitude, $longitude);

        if (!$response) {
            return response()->json([
                'status' => [
                    'code'    => 404,
                    'message' => 'Localização não encontrada'
                ]
            ], 404);
        }

        if (isset($response['error']) && $response['error']) {
            return response()->json([
                'status' => [
                    'code'    => 500,
                    'message' => 'Erro ao buscar geolocalização'
                ],
                'error' => $response['error']
            ], 500);
        }

        $user = User::where('id', Auth::id())->first();

        $user->localizacao = $response;
        $user->save();

        return response()->json([
            'status' => [
                'code'      => 200,
                'message'   => 'Localização atualizada com sucesso'
            ],
            'user' => $user
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();

        try {
            $clima = WeatherService::clima_atual($user);
        } catch (Exception $e) {
            $clima = [];
        }

        try {
            $atividades_recomendadas = AIService::recomendar_atividades($user, $clima);
        } catch (Exception $e) {
            $atividades_recomendadas = "<p>Os serviços de IA estão offline</p>";
        }

        return response()->json([
            'status' => [
                'code'      => 200,
                'message'   => 'Dashboard carregado com sucesso'
            ],
            'clima'                     => $clima,
            'atividades_recomendadas'   => $atividades_recomendadas
        ]);
    }
}
