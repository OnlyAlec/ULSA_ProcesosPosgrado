<?php

class Student
{
    private string $firstName;
    private string $maternalSurname;
    private string $paternalSurname;
    private int $ulsaID;
    private string $typeDesc;
    private string $area;
    private string $email;
    private bool $sed;

    public function __construct($firstName, $maternalSurname, $paternalSurname, $ulsaID, $typeDesc, $area)
    {
        $this->firstName = $firstName;
        $this->maternalSurname = $maternalSurname;
        $this->paternalSurname = $paternalSurname;
        $this->typeDesc = $typeDesc;
        $this->area = $area;
        $validatedId = $this->validateUlsaId($ulsaID);
        if ($validatedId === -1) {
            throw new InvalidArgumentException("Invalid ULSA ID ($ulsaID) - $firstName $maternalSurname");
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

        if (strlen($ulsa_id) == 6)
            return (int) $ulsa_id;
        return -1;
    }

    public function getName()
    {
        return $this->firstName;
    }

    public function getApm()
    {
        return $this->maternalSurname;
    }

    public function getApp()
    {
        return $this->paternalSurname;
    }

    public function getUlsaId()
    {
        return $this->ulsaID;
    }

    public function getTypeDesc()
    {
        return $this->typeDesc;
    }

    public function setTypeDesc($typeDesc)
    {
        $this->typeDesc = $typeDesc;
    }

    public function getArea()
    {
        return $this->area;
    }

    public function setArea($area)
    {
        $this->area = $area;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getSed()
    {
        return $this->sed;
    }

    public function setSed($sed)
    {
        $this->sed = $sed;
    }
}