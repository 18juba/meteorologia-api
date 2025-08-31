<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use App\Interfaces\Address;
use App\DTOs\AddressDto;

trait MapTrait
{
    const NOMINATIM_URL = "https://nominatim.openstreetmap.org";
    const VIACEP_URL    = "https://viacep.com.br/ws";

    protected function search(Address $address): ?array
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

        $n = $this->search($address);
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

    public function reverse_search(float $latitude, float $longitude)
    {
        $params = [
            'lat'            => $latitude,
            'lon'            => $longitude,
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

        $data = $response->json();
        
        if (empty($data)) {
            return null;
        }
        
        return $data;
    }
}
