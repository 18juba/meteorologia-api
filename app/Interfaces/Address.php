<?php
namespace App\Interfaces;

interface Address
{
    public function getAmenity(): ?string;
    public function setAmenity(?string $amenity): void;

    public function getStreet(): ?string;
    public function setStreet(?string $street): void;

    public function getCity(): ?string;
    public function setCity(?string $city): void;

    public function getCounty(): ?string;
    public function setCounty(?string $county): void;

    public function getState(): ?string;
    public function setState(?string $state): void;

    public function getCountry(): ?string;
    public function setCountry(?string $country): void;

    public function getPostalCode(): ?string;
    public function setPostalCode(?string $postalCode): void;
}