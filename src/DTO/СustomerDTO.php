<?php

namespace App\DTO;

class СustomerDTO
{
    function __construct(
        private readonly int       $id,
        private readonly string    $name,
        private readonly string    $surname,
        private readonly string    $comment,
        private readonly \DateTime $birthdate,
        private readonly string    $email,
        private readonly bool      $shouldReceivePromoActionsInfo,
        private readonly bool      $shouldReceiveOrderStatusNotifications,
        private readonly string    $gender,
        private readonly string    $type,
    )
    {
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'comment' => $this->comment,
            'birthdate' => $this->birthdate,
            'email' => $this->email,
            'shouldReceivePromoActionsInfo' => $this->shouldReceivePromoActionsInfo,
            'shouldReceiveOrderStatusNotifications' => $this->shouldReceiveOrderStatusNotifications,
            'gender' => $this->gender,
            'type' => $this->type,
        ];

        // Удаляем ключи с пустыми или null значениями
        return array_filter($data, function ($value) {
            return $value !== null && $value !== '';
        });
    }


}