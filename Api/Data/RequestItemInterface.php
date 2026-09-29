<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api\Data;

interface RequestItemInterface
{
    public const string ID = 'id';
    public const string REQUEST_ID = 'request_id';
    public const string PRODUCT_ID = 'product_id';
    public const string SKU = 'sku';
    public const string NAME = 'name';
    public const string QTY = 'qty';
    public const string ORIGINAL_PRICE = 'original_price';
    public const string SPECIAL_PRICE = 'special_price';

    /**
     * Return entity ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Return parent request ID.
     *
     * @return int
     */
    public function getRequestId(): int;

    /**
     * Set parent request ID.
     *
     * @param int $requestId
     * @return $this
     */
    public function setRequestId(int $requestId): self;

    /**
     * Return product ID (null when the product was deleted).
     *
     * @return int|null
     */
    public function getProductId(): ?int;

    /**
     * Set product ID.
     *
     * @param int|null $productId
     * @return $this
     */
    public function setProductId(?int $productId): self;

    /**
     * Return SKU.
     *
     * @return string
     */
    public function getSku(): string;

    /**
     * Set SKU.
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self;

    /**
     * Return product name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set product name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Return requested / approved qty.
     *
     * @return float
     */
    public function getQty(): float;

    /**
     * Set qty.
     *
     * @param float $qty
     * @return $this
     */
    public function setQty(float $qty): self;

    /**
     * Return catalog price at request time.
     *
     * @return float|null
     */
    public function getOriginalPrice(): ?float;

    /**
     * Set catalog price at request time.
     *
     * @param float|null $price
     * @return $this
     */
    public function setOriginalPrice(?float $price): self;

    /**
     * Return approved special price.
     *
     * @return float|null
     */
    public function getSpecialPrice(): ?float;

    /**
     * Set approved special price.
     *
     * @param float|null $price
     * @return $this
     */
    public function setSpecialPrice(?float $price): self;
}
