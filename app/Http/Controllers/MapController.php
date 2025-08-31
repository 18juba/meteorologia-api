<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DTOs\AddressDto;
use App\Traits\MapTrait;

class MapController extends Controller
{
    use MapTrait;

    public function busca_por_nome(Request $request)
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

        $n = $this->search($dto);
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

    public function busca_reversa(Request $request)
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

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

        return response()->json([
            'status' => [
                'code'    => 200,
                'message' => 'Localização encontrada com sucesso'
            ],
            'localizacao' => $response
        ], 200);
    }
}
