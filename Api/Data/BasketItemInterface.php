<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api\Data;

interface BasketItemInterface
{
    public const string ID = 'id';
    public const string CUSTOMER_ID = 'customer_id';
    public const string PRODUCT_ID = 'product_id';
    public const string QTY = 'qty';
    public const string BUY_REQUEST = 'buy_request';
    public const string CREATED_AT = 'created_at';

    /**
     * Return entity ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Return customer ID.
     *
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * Set customer ID.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * Return product ID.
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Set product ID.
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Return requested qty.
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Set requested qty.
     *
     * @param float $qty
     * @return $this
     */
    public function setQty(float $qty): self;

    /**
     * Return buy request (product options) used to add the product to cart later.
     *
     * @return array
     */
    public function getBuyRequest(): array;

    /**
     * Set buy request.
     *
     * @param array $buyRequest
     * @return $this
     */
    public function setBuyRequest(array $buyRequest): self;

    /**
     * Return created at timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
