<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Data;

use Aheadworks\FeiSpecialPricing\Api\Data\ItemPriceInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class ItemPrice extends AbstractSimpleObject implements ItemPriceInterface
{
    /**
     * Return request item ID.
     *
     * @return int
     */
    public function getItemId(): int
    {
        return (int) $this->_get(self::ITEM_ID);
    }

    /**
     * Set request item ID.
     *
     * @param int $itemId
     * @return $this
     */
    public function setItemId(int $itemId): self
    {
        return $this->setData(self::ITEM_ID, $itemId);
    }

    /**
     * Return approved special price per unit.
     *
     * @return float
     */
    public function getSpecialPrice(): float
    {
        return (float) $this->_get(self::SPECIAL_PRICE);
    }

    /**
     * Set approved special price per unit.
     *
     * @param float $specialPrice
     * @return $this
     */
    public function setSpecialPrice(float $specialPrice): self
    {
        return $this->setData(self::SPECIAL_PRICE, $specialPrice);
    }
}
