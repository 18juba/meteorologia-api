<?php

namespace App\Services;

use App\Models\User;
use App\Traits\WeatherTrait;
use Illuminate\Support\Facades\Http;

class WeatherService
{
    use WeatherTrait;

    public static function clima_atual(User $user)
    {
        $localizacao = json_decode($user->localizacao);

        $latitude = $localizacao->lat;
        $longitude = $localizacao->lon;

        $params = [
            "latitude" => $latitude,
            "longitude" => $longitude,
            "current" => [
                "temperature_2m",
                "relative_humidity_2m",
                "apparent_temperature",
                "is_day",
                "snowfall",
                "showers",
                "precipitation",
                "rain",
                "surface_pressure",
                "pressure_msl",
                "cloud_cover",
                "weather_code",
                "wind_gusts_10m",
                "wind_direction_10m",
                "wind_speed_10m",
            ],
            "daily" => [
                "temperature_2m_max",
                "temperature_2m_min",
                "apparent_temperature_max",
                "apparent_temperature_min",
                "uv_index_max",
                "uv_index_clear_sky_max",
                "sunrise",
                "sunset"
            ]
        ];

        $response = Http::get("https://api.open-meteo.com/v1/forecast", $params);
        $data = $response->json();

        if (!$response->successful() || !isset($data['current'])) {
            return response()->json([
                "error" => true,
                "message" => "Não foi possível obter o clima atual"
            ], 500);
        }

        $current = $data['current'];
        $daily  = $data['daily'];

        $previsao_diaria = [
            "data"                          => $daily["time"][0] ?? null,
            "temperatura_minima"            => $daily["temperature_2m_min"][0] ?? null,
            "temperatura_maxima"            => $daily["temperature_2m_max"][0] ?? null,
            "sensacao_termica_minima"       => $daily["apparent_temperature_min"][0] ?? null,
            "sensacao_termica_maxima"       => $daily["apparent_temperature_max"][0] ?? null,
            "indice_uv_maximo"              => $daily["uv_index_max"][0] ?? null,
            "indice_uv_ceu_limpo_maximo"    => $daily["uv_index_clear_sky_max"][0] ?? null,
            "nascer_do_sol"                 => $daily["sunrise"][0] ?? null,
            "por_do_sol"                    => $daily["sunset"][0] ?? null,
        ];

        $weatherCodes = [
            0  => "Céu limpo",
            1  => "Principalmente limpo",
            2  => "Parcialmente nublado",
            3  => "Nublado",
            45 => "Névoa",
            48 => "Névoa gelada",
            51 => "Garoa leve",
            53 => "Garoa moderada",
            55 => "Garoa intensa",
            56 => "Garoa congelante leve",
            57 => "Garoa congelante intensa",
            61 => "Chuva leve",
            63 => "Chuva moderada",
            65 => "Chuva forte",
            66 => "Chuva congelante leve",
            67 => "Chuva congelante forte",
            71 => "Neve leve",
            73 => "Neve moderada",
            75 => "Neve intensa",
            77 => "Granizo de neve",
            80 => "Aguaceiro leve",
            81 => "Aguaceiro moderado",
            82 => "Aguaceiro violento",
            85 => "Nevada leve",
            86 => "Nevada forte",
            95 => "Trovoada leve ou moderada",
            96 => "Trovoada com granizo leve",
            99 => "Trovoada com granizo forte",
        ];

        $codigo = $current["weather_code"];
        $descricaoCodigo = $weatherCodes[$codigo] ?? "Condição desconhecida";

        $climaAtual = [
            "hora"                      => $current["time"],
            "temperatura"               => $current["temperature_2m"],
            "umidade_relativa"          => $current["relative_humidity_2m"],
            "sensacao_termica"          => $current["apparent_temperature"],
            "periodo_do_dia"            => $current["is_day"] ? "Dia" : "Noite",
            "neve"                      => $current["snowfall"],
            "chuvas_fortes"             => $current["showers"],
            "precipitacao"              => $current["precipitation"],
            "chuva"                     => $current["rain"],
            "pressao_superficie"        => $current["surface_pressure"],
            "pressao_nivel_do_mar"      => $current["pressure_msl"],
            "cobertura_de_nuvens"       => $current["cloud_cover"],
            "condicao_climatica"        => $descricaoCodigo,
            "rajadas_de_vento"          => $current["wind_gusts_10m"],
            "direcao_do_vento"          => $current["wind_direction_10m"],
            "velocidade_do_vento"       => $current["wind_speed_10m"],
        ];

        return [
            'previsao_diaria'   => $previsao_diaria,
            'clima_atual'       => $climaAtual
        ];
    }
}
