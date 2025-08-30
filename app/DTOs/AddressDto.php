<?php
namespace App\DTOs;

use App\Interfaces\Address;

class AddressDto implements Address
{
    protected ?string $amenity = null;
    protected ?string $street  = null;
    protected ?string $city    = null;
    protected ?string $county  = null;
    protected ?string $state   = null;
    protected ?string $country = null;
    protected ?string $postalCode = null;

    protected ?float $latitude  = null;
    protected ?float $longitude = null;
    protected ?string $displayName = null;

    public function getAmenity(): ?string { return $this->amenity; }
    public function setAmenity(?string $amenity): void { $this->amenity = $amenity; }

    public function getStreet(): ?string { return $this->street; }
    public function setStreet(?string $street): void { $this->street = $street; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): void { $this->city = $city; }

    public function getCounty(): ?string { return $this->county; }
    public function setCounty(?string $county): void { $this->county = $county; }

    public function getState(): ?string { return $this->state; }
    public function setState(?string $state): void { $this->state = $state; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): void { $this->country = $country; }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): void { $this->postalCode = $postalCode; }

    public function setLatitude(?float $lat): void { $this->latitude = $lat; }
    public function getLatitude(): ?float { return $this->latitude; }

    public function setLongitude(?float $lon): void { $this->longitude = $lon; }
    public function getLongitude(): ?float { return $this->longitude; }

    public function setDisplayName(?string $name): void { $this->displayName = $name; }
    public function getDisplayName(): ?string { return $this->displayName; }

    public function toArray(): array
    {
        return [
            'amenity' => $this->amenity,
            'street' => $this->street,
            'city' => $this->city,
            'county' => $this->county,
            'state' => $this->state,
            'country' => $this->country,
            'postalcode' => $this->postalCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'display_name' => $this->displayName,
        ];
    }

    public static function fromViaCep(array $resp): self
    {
        $a = new self();
        $a->setStreet($resp['logradouro'] ?? null);
        $a->setCounty($resp['bairro'] ?? null);
        $a->setCity($resp['localidade'] ?? null);
        $a->setState($resp['uf'] ?? null);
        $a->setPostalCode($resp['cep'] ?? null);
        $a->setCountry('Brazil');
        return $a;
    }
}
