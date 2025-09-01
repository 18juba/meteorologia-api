<?php

namespace App\Services;

use App\Models\User;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

class AIService
{
    public static function recomendar_atividades(User $user, ?array $clima): ?string
    {
        $local = json_decode($user->localizacao ?? '{}', true);

        $payload = [
            'nome' => $user->nome,
            'hobbies' => $user->hobbies ?? null,
            'cidade' => $local['address']['city'] ?? null,
            'estado' => $local['address']['state'] ?? null,
            'pais' => $local['address']['country'] ?? null,
        ];

        $climaPayload = [
            'dia'                   => $clima['previsao_diaria']['data'],
            'temperatura_maxima'    => $clima['previsao_diaria']['temperatura_maxima'],
            'temperatura_minima'    => $clima['previsao_diaria']['temperatura_minima'],
            'indice_uv_maximo'      => $clima['previsao_diaria']['indice_uv_maximo'],
            'periodo_do_dia'        => $clima['clima_atual']['periodo_do_dia'],
            'condicao_climatica'    => $clima['clima_atual']['condicao_climatica'],
            'temperatura_atual'     => $clima['clima_atual']['temperatura'],
            'precipitacao'          => $clima['clima_atual']['precipitacao']
        ];

        $userJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $climaJson = json_encode($climaPayload, JSON_UNESCAPED_UNICODE);

        $response = Prism::text()
            ->using(Provider::Ollama, 'gemma3:1b')
            ->withSystemPrompt(view('prompts.card-atividades'))
            ->withPrompt(
                "DadosUsuario:\n```json\n{$userJson}\n```\n\n" .
                    "DadosClima:\n```json\n{$climaJson}\n```\n\n" .
                    "Instruções: gere apenas o fragmento HTML conforme o system prompt; use os hobbies do usuário apenas como base, o clima apenas de forma implícita; se não houver hobbies, apenas peça para o usuário ir no perfil e adicionar."
            )
            ->usingTemperature(0.5)
            ->withMaxTokens(400)
            ->asText();

        return $response->text;
    }
}
