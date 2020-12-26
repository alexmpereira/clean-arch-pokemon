<?php

declare(strict_types=1);

namespace App\Player\UseCases\Contracts;

use App\Player\Domain\Player;

interface DebitMoneyRepository
{
    /**
     * @param Player $player
     * @param float $money
     * @return bool
     */
    public function debitMoney(Player $player, float $money): bool;
}