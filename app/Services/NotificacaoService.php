<?php

namespace App\Services;

use App\Models\Notificacao;
use Exception;

class NotificacaoService
{
    public static function emitir(string|int $user_id, string $titulo, string $descricao, ?string $estimativa=null): bool
    {
        $notificacao = Notificacao::create([
            'user_id'       => $user_id,
            'titulo'        => $titulo,
            'descricao'     => $descricao,
            'estimativa'    => $estimativa
        ]);

        if (!$notificacao) {
            return false;
        }

        return true;
    }

    public static function ler(Notificacao $notificacao): bool
    {
        try {
            $notificacao->lido = true;
            $notificacao->save();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
