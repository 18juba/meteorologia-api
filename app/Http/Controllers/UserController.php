<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use App\Models\User;
use App\Services\AIService;
use App\Services\NotificacaoService;
use App\Services\WeatherService;
use App\Traits\WeatherTrait;
use App\Validations\UserValidation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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
        ]);

        NotificacaoService::emitir(
            $user->id,
            'Nova Conta',
            'Bem vindo ' . $user->nome .  ', ao app meteorologia da MP Solutions'
        );

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

        Cache::forget("clima_user_{$user->id}");
        Cache::forget("atividades_user_{$user->id}");
        Cache::forget("cards_user_{$user->id}");

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

        Cache::forget("clima_user_{$user->id}");
        Cache::forget("atividades_user_{$user->id}");
        Cache::forget("cards_user_{$user->id}");

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

        $clima = Cache::remember(
            "clima_user_{$user->id}",
            now()->addMinute(),
            function () use ($user) {
                try {
                    return WeatherService::clima_atual($user);
                } catch (\Exception $e) {
                    return [];
                }
            }
        );

        $atividades_recomendadas = Cache::get("atividades_user_{$user->id}");

        if (!$atividades_recomendadas) {
            try {
                $atividades_recomendadas = AIService::recomendar_atividades($user, $clima);
                Cache::put("atividades_user_{$user->id}", $atividades_recomendadas, now()->addMinutes(30));
            } catch (\Exception $e) {
                Log::warning($e);
                $atividades_recomendadas = "<p>Os serviços de IA estão offline</p>";
            }
        }

        $cards_dashboard = Cache::get("cards_user_{$user->id}");

        if (!$cards_dashboard) {
            try {
                $cards_dashboard = AIService::cards_dashboard($user, $clima);
                Cache::put("cards_user_{$user->id}", $cards_dashboard, now()->addMinutes(30));
            } catch (\Exception $e) {
                Log::warning($e);
                $cards_dashboard = [];
            }
        }

        return response()->json([
            'status' => [
                'code'    => 200,
                'message' => 'Dashboard carregado com sucesso'
            ],
            'clima'         => $clima,
            'cards' => [
                'atividades_recomendadas'   => $atividades_recomendadas,
                'outros_cards'              => $cards_dashboard
            ]
        ]);
    }

    public function notificacoes()
    {
        $notificacoes = Notificacao::where('user_id', Auth::id())
            ->where('lido', false)
            ->get();

        return response()->json([
            'status' => [
                'code'    => 200,
                'message' => 'Notificações carregadas com sucesso'
            ],
            'notificacoes'  => $notificacoes
        ]);
    }

    public function marcar_como_lido(string $id)
    {
        $notificacao = Notificacao::find($id);

        if (!$notificacao) {
            return response()->json([
                'status' => [
                    'code'    => 404,
                    'message' => 'Notificação não encontrada'
                ],
            ], 404);
        }

        $lido = NotificacaoService::ler($notificacao);

        if (!$lido) {
            return response()->json([
                'status' => [
                    'code'    => 500,
                    'message' => 'Erro desconhecido ao ler notificação'
                ],
            ], 500);
        }

        return response()->json([
            'status' => [
                'code'    => 200,
                'message' => 'Notificação lida com sucesso'
            ],
        ], 200);
    }
}
