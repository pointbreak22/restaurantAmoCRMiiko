<?php

namespace App\DTO;

class HookDataDTO
{

    private bool $createdReserve = false;
    private string $leadId;
    private string $contactName = "";
    private string $contactEmail = "";
    private string $contactPhone = "";
    private string $dataReserve = "";
    private string $idReserve = "";

    public function getIdReserve(): string
    {
        return $this->idReserve;
    }

    public function setIdReserve(string $idReserve): void
    {
        $this->idReserve = $idReserve;
    }


    public function isCreatedReserve(): bool
    {
        return $this->createdReserve;
    }

    public function setCreatedReserve(bool $createdReserve): void
    {
        $this->createdReserve = $createdReserve;
    }

    private ?string $nameReserve;
    private string $countPeople = "0";

    private ?string $timeReserve;

    public function getLeadId(): string
    {
        return $this->leadId;
    }

    public function setLeadId(string $leadId): void
    {
        $this->leadId = $leadId;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): void
    {
        $this->contactName = $contactName;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): void
    {
        $this->contactEmail = $contactEmail;
    }

    public function getContactPhone(): string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(string $contactPhone): void
    {
        $this->contactPhone = $contactPhone;
    }

    public function getNameReserve(): string
    {
        return $this->nameReserve;
    }

    public function setNameReserve(string $nameReserve): void
    {
        $this->nameReserve = $nameReserve;
    }

    public function getCountPeople(): string
    {
        return $this->countPeople;
    }

    public function setCountPeople(string $countPeople): void
    {
        $this->countPeople = $countPeople;
    }

    public function getTimeReserve(): string
    {
        return $this->timeReserve;
    }

    public function setTimeReserve(string $timeReserve): void
    {
        $this->timeReserve = $timeReserve;
    }

    public function getDataReserve(): string
    {
        return $this->dataReserve;
    }

    public function setDataReserve(string $dataReserve): void
    {
        $this->dataReserve = $dataReserve;
    }


}