<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

interface BuyRequestAwareInterface
{
    /**
     * Return buy request (product options) used to add the product to the cart.
     *
     * @return array
     */
    public function getBuyRequest(): array;

    /**
     * Set buy request (product options).
     *
     * @param array $buyRequest
     * @return $this
     */
    public function setBuyRequest(array $buyRequest): self;
}
