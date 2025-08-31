<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json([
                    'status' => [
                        'code'      => 401,
                        'message'   => [
                            'title' => 'Erro na Autenticação',
                            'body'  => 'Token não foi enviado, fazer login novamente'
                        ]
                    ]
                ], 401);
            }

            JWTAuth::setToken($token)->authenticate();
        } catch (Exception $e) {
            report($e);

            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json([
                    'status' => [
                        'code'      => 401,
                        'message'   => [
                            'title' => 'Erro na Autenticação',
                            'body'  => 'Token é inválido, corrigir ou fazer login novamente'
                        ]
                    ]
                ], 401);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                if ($request->path() == "api/auth/refresh") {
                    return $next($request);
                }
                return response()->json([
                    'status' => [
                        'code'      => 401,
                        'message'   => [
                            'title' => 'Erro na Autenticação',
                            'body'  => 'Token expirado, fazer login novamente'
                        ]
                    ]
                ], 401);
            } else {
                return response()->json([
                    'status' => [
                        'code'      => 401,
                        'message'   => [
                            'title' => 'Erro na Autenticação',
                            'body'  => 'Token não encontrado, enviar corretamente'
                        ]
                    ]
                ], 401);
            }
        }

        return $next($request);
    }
}