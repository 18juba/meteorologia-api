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
            ->using(Provider::OpenRouter, 'mistralai/mistral-nemo:free')
            ->withSystemPrompt(view('prompts.card-atividades'))
            ->withPrompt(
                "Aqui está os dados do usuário: {$userJson}\n" .
                "Aqui estão os dados do clima: {$climaJson}"
            )
            ->usingTemperature(1)
            ->withMaxTokens(400)
            ->asText();

        return $response->text;
    }

    public static function cards_dashboard(User $user, ?array $clima): ?array
    {
        $local = json_decode($user->localizacao ?? '{}', true);

        $localizacaoPayload = [
            'cidade' => $local['address']['city'] ?? null,
            'estado' => $local['address']['state'] ?? null,
            'pais' => $local['address']['country'] ?? null,
        ];

        $climaPayload = [
            'periodo_do_dia'        => $clima['clima_atual']['periodo_do_dia'],
            'condicao_climatica'    => $clima['clima_atual']['condicao_climatica'],
            'temperatura_atual'     => $clima['clima_atual']['temperatura'],
            'precipitacao'          => $clima['clima_atual']['precipitacao'],
            'indice_uv_maximo'      => $clima['previsao_diaria']['indice_uv_maximo'],
            'velocidade_do_vento'   => $clima['clima_atual']['velocidade_do_vento'],
            'direcao_do_vento'      => $clima['clima_atual']['direcao_do_vento'],
            'rajadas_de_vento'      => $clima['clima_atual']['rajadas_de_vento'],
        ];

        $localizacaoJson = json_encode($localizacaoPayload, JSON_UNESCAPED_UNICODE);
        $climaJson = json_encode($climaPayload, JSON_UNESCAPED_UNICODE);

        $response = Prism::text()
            ->using(Provider::OpenRouter, 'mistralai/mistral-nemo:free')
            ->withSystemPrompt(view('prompts.cards-dashboard'))
            ->withPrompt("
                Aqui estão os dados da localização: {$localizacaoJson}\n.\n
                Aqui estão os dados do clima: {$climaJson}
            ")
            ->usingTemperature(1)
            ->withMaxTokens(400)
            ->asText();

        $clean = preg_replace('/^```(json)?|```$/m', '', $response->text);
        $clean = trim($clean);

        $json = json_decode($clean, true);

        return $json ?: ['raw' => $clean];
    }
}
