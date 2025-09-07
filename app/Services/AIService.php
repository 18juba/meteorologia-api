<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

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
            ->using(Provider::OpenRouter, 'google/gemma-3-4b-it:free')
            ->withSystemPrompt(view('prompts.card-atividades'))
            ->withPrompt(
                "Aqui está os dados do usuário: {$userJson}\n" .
                    "Aqui estão os dados do clima: {$climaJson}"
            )
            ->usingTemperature(0.3)
            ->withMaxTokens(600)
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

        $schema = new ObjectSchema(
            name: 'Análise de identificadores do clima',
            description: 'Análise de cada indicador climático',
            properties: [
                new ObjectSchema(
                    name: 'Temperatura',
                    description: 'Análise da temperatura',
                    properties: [
                        new StringSchema('analise', 'Análise e resumo da temperatura atual')
                    ],
                    requiredFields: ['analise']
                ),
                new ObjectSchema(
                    name: 'UV',
                    description: 'Análise do índice UV',
                    properties: [
                        new StringSchema('analise', 'Análise e resumo da índice UV')
                    ],
                    requiredFields: ['analise']
                ),
                new ObjectSchema(
                    name: 'Precipitação',
                    description: 'Análise da precipitação',
                    properties: [
                        new StringSchema('analise', 'Análise e resumo da precipitação')
                    ],
                    requiredFields: ['analise']
                ),
                new ObjectSchema(
                    name: 'Ventos',
                    description: 'Análise dos Ventos',
                    properties: [
                        new StringSchema('analise', 'Análise e resumo dos Ventos')
                    ],
                    requiredFields: ['analise']
                ),
                new ObjectSchema(
                    name: 'Umidade',
                    description: 'Análise da umidade',
                    properties: [
                        new StringSchema('analise', 'Análise e resumo da umidade')
                    ],
                    requiredFields: ['analise']
                ),
                new StringSchema('condicao', 'A partir de todos os dados climáticos, deve retornar um desses indicadores entre: “very hot,” “very cold,” “very windy,” “very wet,” or “very uncomfortable”')
            ],
            requiredFields: ['Temperatura', 'UV', 'Precipitação', 'Ventos', 'Umidade', 'condicao']
        );

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

        $response = Prism::structured()
            ->using(Provider::OpenRouter, 'deepseek/deepseek-chat-v3-0324:free')
            ->withSystemPrompt(view('prompts.cards-dashboard'))
            ->withPrompt("
                Aqui estão os dados da localização: {$localizacaoJson}\n.\n
                Aqui estão os dados do clima: {$climaJson}
            ")
            ->withSchema($schema)
            ->usingTemperature(0.25)
            ->withMaxTokens(700)
            ->asStructured();

        $clean = preg_replace('/^```(json)?|```$/m', '', $response->text);
        $clean = trim($clean);

        $json = json_decode($clean, true);

        return $json ?: ['raw' => $clean];
    }

    public static function rate_limit()
    {
        $apiKey = config('prism.providers.openrouter.api_key');
        $url = config('prism.providers.openrouter.url') . '/key';

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
        ])->get($url);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => true,
            'status' => $response->status(),
            'message' => $response->body(),
        ];
    }
}
