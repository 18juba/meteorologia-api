<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Interfaces\Address;
use App\DTOs\AddressDto;

class MapController extends Controller
{
    const NOMINATIM_URL = "https://nominatim.openstreetmap.org";
    const VIACEP_URL    = "https://viacep.com.br/ws";

    protected function nominatim_search(Address $address): ?array
    {
        $params = array_filter([
            'amenity' => $address->getAmenity(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'county' => $address->getCounty(),
            'state' => $address->getState(),
            'country' => $address->getCountry(),
            'postalcode' => $address->getPostalCode(),
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => 1,
        ]);

        $userAgent = config('app.name', 'app_meteorologia');

        if ($email = config('services.nominatim.email')) {
            $params['email'] = $email;
        }

        $response = Http::withHeaders([
            'User-Agent' => $userAgent,
            'Accept-Language' => 'pt-BR'
        ])->get(self::NOMINATIM_URL . '/search', $params);

        if (! $response->ok()) {
            return null;
        }

        $data = $response->json();
        if (empty($data) || !is_array($data)) {
            return null;
        }

        return $data[0] ?? null;
    }

    protected function cep_search(string $cep): ?AddressDto
    {
        $cleanCep = preg_replace('/\D+/', '', $cep);
        if (strlen($cleanCep) !== 8) {
            return null;
        }

        $resp = Http::get(self::VIACEP_URL . "/{$cleanCep}/json/");
        if (! $resp->ok()) {
            return null;
        }

        $json = $resp->json();

        if (isset($json['erro']) && $json['erro'] === true) {
            return null;
        }

        $address = AddressDto::fromViaCep($json);

        $n = $this->nominatim_search($address);
        if ($n) {
            if (isset($n['lat'])) {
                $address->setLatitude((float) $n['lat']);
            }
            if (isset($n['lon'])) {
                $address->setLongitude((float) $n['lon']);
            }
            $address->setDisplayName($n['display_name'] ?? null);
            if (isset($n['address']) && is_array($n['address'])) {
                $addr = $n['address'];
                $address->setCountry($addr['country'] ?? $address->getCountry());
                $address->setCity($addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $address->getCity());
            }
        }

        return $address;
    }

    public function search(Request $request)
    {
        if ($request->filled('cep')) {
            $address = $this->cep_search($request->cep);
            if (! $address) {
                return response()->json([
                    'status' => [
                        'code'      => 404,
                        'message'   => 'CEP inválido ou não encontrado'
                    ]
                ], 404);
            }
            return response()->json([
                'status' => [
                    'code'      => 200,
                    'message'   => 'Busca por CEP bem sucedida'
                ],
                'localizacao' => $address->toArray()
            ], 200);
        }

        $dto = new AddressDto();
        $dto->setAmenity($request->input('amenity'));
        $dto->setStreet($request->input('street'));
        $dto->setCity($request->input('city'));
        $dto->setCounty($request->input('county'));
        $dto->setState($request->input('state'));
        $dto->setCountry($request->input('country'));
        $dto->setPostalCode($request->input('postalcode'));

        $n = $this->nominatim_search($dto);
        if (! $n) {
            return response()->json([
                'status' => [
                    'code'      => 404,
                    'message'   => 'Nenhum resultado encontrado'
                ]
            ], 404);
        }

        if (isset($n['lat'])) $dto->setLatitude((float)$n['lat']);
        if (isset($n['lon'])) $dto->setLongitude((float)$n['lon']);
        $dto->setDisplayName($n['display_name'] ?? null);

        return response()->json([
            'status' => [
                'code'      => 200,
                'message'   => 'Localização encontrada com sucesso'
            ],
            'localizacao' => $dto->toArray()
        ], 200);
    }

    public function reverse_search(Request $request)
    {
        $lat = $request->input('latitude');
        $lon = $request->input('longitude');

        if (! $lat || ! $lon) {
            return response()->json([
                'status' => [
                    'code'    => 400,
                    'message' => 'Latitude e longitude são obrigatórias'
                ]
            ], 400);
        }

        $params = [
            'lat'            => $lat,
            'lon'            => $lon,
            'format'         => 'json',
            'addressdetails' => 1,
            'zoom'           => 18, // nível de detalhe (0=país, 18=edifício)
        ];

        $userAgent = config('app.name', 'app_meteorologia');

        if ($email = config('services.nominatim.email')) {
            $params['email'] = $email;
        }

        $response = Http::withHeaders([
            'User-Agent'       => $userAgent,
            'Accept-Language'  => 'pt-BR'
        ])->get(self::NOMINATIM_URL . '/reverse', $params);

        if (! $response->ok()) {
            return response()->json([
                'status' => [
                    'code'    => 500,
                    'message' => 'Erro ao consultar o serviço de geocodificação reversa'
                ]
            ], 500);
        }

        $data = $response->json();

        if (empty($data) || !isset($data['address'])) {
            return response()->json([
                'status' => [
                    'code'    => 404,
                    'message' => 'Nenhuma localização encontrada'
                ]
            ], 404);
        }

        $dto = new AddressDto();
        $addr = $data['address'];

        $dto->setLatitude((float)$lat);
        $dto->setLongitude((float)$lon);
        $dto->setDisplayName($data['display_name'] ?? null);
        $dto->setCountry($addr['country'] ?? null);
        $dto->setState($addr['state'] ?? null);
        $dto->setCity($addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null);
        $dto->setCounty($addr['county'] ?? null);
        $dto->setStreet(($addr['road'] ?? '') . ' ' . ($addr['house_number'] ?? ''));
        $dto->setPostalCode($addr['postcode'] ?? null);

        return response()->json([
            'status' => [
                'code'    => 200,
                'message' => 'Endereço encontrado com sucesso'
            ],
            'localizacao' => $dto->toArray()
        ], 200);
    }
}
