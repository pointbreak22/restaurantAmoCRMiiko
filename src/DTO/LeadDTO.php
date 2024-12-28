<?php

namespace App\DTO;

class LeadDTO
{
    private bool $createdReserve = false;
    private string $leadId;
    private string $contactName;
    private string $contactEmail;
    private string $contactPhone;
    private string $dataReserve;
    private string $idReserve;
    private float $sumReserve;
    private ?string $nameReserve;
    private string $countPeople;
    private ?string $timeReserve;

    public function isCreatedReserve(): bool
    {
        return $this->createdReserve;
    }

    public function setCreatedReserve(bool $createdReserve): LeadDTO
    {
        $this->createdReserve = $createdReserve;
        return $this;
    }

    public function getLeadId(): string
    {
        return $this->leadId ?? '';
    }

    public function setLeadId(string $leadId): LeadDTO
    {
        $this->leadId = $leadId;
        return $this;
    }

    public function getContactName(): string
    {
        return $this->contactName ?? '';
    }

    public function setContactName(string $contactName): LeadDTO
    {
        $this->contactName = $contactName;
        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail ?? '';
    }

    public function setContactEmail(string $contactEmail): LeadDTO
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function getContactPhone(): string
    {
        return $this->contactPhone ?? '';
    }

    public function setContactPhone(string $contactPhone): LeadDTO
    {
        $this->contactPhone = $contactPhone;
        return $this;
    }

    public function getDataReserve(): string
    {
        return $this->dataReserve ?? '';
    }

    public function setDataReserve(string $dataReserve): LeadDTO
    {
        $this->dataReserve = $dataReserve;
        return $this;
    }

    public function getIdReserve(): string
    {
        return $this->idReserve ?? '';
    }

    public function setIdReserve(string $idReserve): LeadDTO
    {
        $this->idReserve = $idReserve;
        return $this;
    }

    public function getSumReserve(): float
    {
        return $this->sumReserve ?? 0;
    }

    public function setSumReserve(float $sumReserve): LeadDTO
    {
        $this->sumReserve = $sumReserve;
        return $this;
    }

    public function getNameReserve(): ?string
    {
        return $this->nameReserve ?? '';
    }

    public function setNameReserve(?string $nameReserve): LeadDTO
    {
        $this->nameReserve = $nameReserve;
        return $this;
    }

    public function getCountPeople(): string
    {
        return $this->countPeople ?? '';
    }

    public function setCountPeople(string $countPeople): LeadDTO
    {
        $this->countPeople = $countPeople;
        return $this;
    }

    public function getTimeReserve(): ?string
    {
        return $this->timeReserve ?? '';
    }

    public function setTimeReserve(?string $timeReserve): LeadDTO
    {
        $this->timeReserve = $timeReserve;
        return $this;
    }

}