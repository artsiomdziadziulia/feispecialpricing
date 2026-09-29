<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api\Data;

interface ItemPriceInterface
{
    public const string ITEM_ID = 'item_id';
    public const string SPECIAL_PRICE = 'special_price';

    /**
     * Return request item ID.
     *
     * @return int
     */
    public function getItemId(): int;

    /**
     * Set request item ID.
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): self;

    /**
     * Return approved special price per unit.
     *
     * @return float
     */
    public function getSpecialPrice(): float;

    /**
     * Set approved special price per unit.
     *
     * @param float $specialPrice
     * @return $this
     */
    public function setSpecialPrice(float $specialPrice): self;
}
