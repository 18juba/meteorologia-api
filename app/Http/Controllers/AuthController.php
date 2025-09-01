<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !(Hash::check($request->senha, $user->senha))) {
                return response()->json([
                    'status' => [
                        'code'       => 404,
                        'message'    => 'Email ou Senha incorreto'
                    ]
                ], 404);
            }

            if (!$user->ativo) {
                return response()->json([
                    'status' => [
                        'code'       => 403,
                        'message'    => 'Sua conta está desativada'
                    ]
                ], 403);
            }

            $token = JWTAuth::fromUser($user);

            $user = JWTAuth::setToken($token)->toUser();

            $saudacoes = AIService::hello();

            return response()->json([
                'status' => [
                    'code'      => 200,
                    'message'   => 'Autenticado com Sucesso'
                ],
                'token'     => $token,
                'user'      => $user,
                'saudacoes' => $saudacoes
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => [
                    'code'      => 500,
                    'message'   => 'Erro desconhecido ao autenticar'
                ]
            ], 500);
        }
    }
}
