<?php

namespace App\DTO;

class ReserveDTO
{

    private string $organizationId;
    private string $terminalGroupId;
    private string $productId;
    private ?CustomerDTO $customer;
    private string $phone;
    private array $tables;
    private string $durationInMinutes;
    private string $dateVisit;
    private string $customerCount;

    public function getCustomerCount(): string
    {
        return $this->customerCount;
    }

    public function setCustomerCount(string $customerCount): void
    {
        $this->customerCount = $customerCount;
    }

    public function getOrganizationId(): string
    {
        return $this->organizationId;
    }

    public function setOrganizationId(string $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function getTerminalGroupId(): string
    {
        return $this->terminalGroupId;
    }

    public function setTerminalGroupId(string $terminalGroupId): void
    {
        $this->terminalGroupId = $terminalGroupId;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getCustomer(): ?CustomerDTO
    {
        return $this->customer;
    }

    public function setCustomer(?CustomerDTO $customer): void
    {
        $this->customer = $customer;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function setTables(array $tables): void
    {
        $this->tables = $tables;
    }

    public function getDurationInMinutes(): string
    {
        return $this->durationInMinutes;
    }

    public function setDurationInMinutes(string $durationInMinutes): void
    {
        $this->durationInMinutes = $durationInMinutes;
    }

    public function getDateVisit(): string
    {
        return $this->dateVisit;
    }

    public function setDateVisit(string $dateVisit): void
    {
        $this->dateVisit = $dateVisit;
    }


}