<?php

class StudentBase
{
    private string $firstName;
    private string $lastName;
    private int $ulsaID;
    private string $carrer;
    private string $email;

    public function __construct($firstName, $lastName, $ulsaID, $carrer, $email)

    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->carrer = $carrer;
        $this->email = $email;

        $validatedId = $this->validateUlsaId($ulsaID);
        if ($validatedId === -1) {
            throw new InvalidArgumentException("Invalid ULSA ID ($ulsaID) - $firstName $lastName");
        }
        $this->ulsaID = $validatedId;
    }

    private function normalizeUlsaId($ulsaId)
    {
        return preg_replace('/^al(\d{6})$/i', '$1', $ulsaId);
    }

    private function validateUlsaId($ulsa_id)
    {
        $ulsa_id = $this->normalizeUlsaId($ulsa_id);

        if (strlen($ulsa_id) == 6) {
            return (int) $ulsa_id;
        }
        return -1;
    }

    public function getJSON()
    {
        return [
            'firstName' => $this->getName(),
            'lastName' => $this->getLastName(),
            'ulsaID' => $this->getUlsaId(),
            'carrer' => $this->getCarrer(),
            'email' => $this->getEmail()
        ];
    }

    public function getName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getUlsaId()
    {
        return $this->ulsaID;
    }

    public function getCarrer()
    {
        return $this->carrer;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }
}

class Student extends StudentBase
{
    private bool $sed;
    private bool $afi;

    public function getJSON()
    {
        $json = parent::getJSON();
        $json['sed'] = $this->getSed();
        $json['afi'] = $this->getAfi();
        return $json;
    }

    public function getSed()
    {
        return $this->sed;
    }

    public function setSed($sed)
    {
        $this->sed = $sed;
    }

    public function getAfi()
    {
        return $this->afi;
    }

    public function setAfi($afi)
    {
        $this->afi = $afi;
    }
}