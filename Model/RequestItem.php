<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem as RequestItemResource;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\SerializerInterface;

class RequestItem extends AbstractModel implements RequestItemInterface, BuyRequestAwareInterface
{
    use BuyRequestDataTrait;

    public const string BUY_REQUEST = 'buy_request';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param SerializerInterface $serializer
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        private readonly SerializerInterface $serializer,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(RequestItemResource::class);
    }

    /**
     * Return entity ID.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        $id = $this->getData(self::ID);

        return $id !== null ? (int) $id : null;
    }

    /**
     * Return parent request ID.
     *
     * @return int
     */
    public function getRequestId(): int
    {
        return (int) $this->getData(self::REQUEST_ID);
    }

    /**
     * Set parent request ID.
     *
     * @param int $requestId
     * @return $this
     */
    public function setRequestId(int $requestId): self
    {
        return $this->setData(self::REQUEST_ID, $requestId);
    }

    /**
     * Return product ID.
     *
     * @return int|null
     */
    public function getProductId(): ?int
    {
        $value = $this->getData(self::PRODUCT_ID);

        return $value !== null ? (int) $value : null;
    }

    /**
     * Set product ID.
     *
     * @param int|null $productId
     * @return $this
     */
    public function setProductId(?int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * Return SKU.
     *
     * @return string
     */
    public function getSku(): string
    {
        return (string) $this->getData(self::SKU);
    }

    /**
     * Set SKU.
     *
     * @param string $sku
     * @return $this
     */
    public function setSku(string $sku): self
    {
        return $this->setData(self::SKU, $sku);
    }

    /**
     * Return product name.
     *
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * Set product name.
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Return qty.
     *
     * @return float
     */
    public function getQty(): float
    {
        return (float) $this->getData(self::QTY);
    }

    /**
     * Set qty.
     *
     * @param float $qty
     * @return $this
     */
    public function setQty(float $qty): self
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * Return catalog price at request time.
     *
     * @return float|null
     */
    public function getOriginalPrice(): ?float
    {
        $value = $this->getData(self::ORIGINAL_PRICE);

        return $value !== null ? (float) $value : null;
    }

    /**
     * Set catalog price at request time.
     *
     * @param float|null $price
     * @return $this
     */
    public function setOriginalPrice(?float $price): self
    {
        return $this->setData(self::ORIGINAL_PRICE, $price);
    }

    /**
     * Return approved special price.
     *
     * @return float|null
     */
    public function getSpecialPrice(): ?float
    {
        $value = $this->getData(self::SPECIAL_PRICE);

        return $value !== null && $value !== '' ? (float) $value : null;
    }

    /**
     * Set approved special price.
     *
     * @param float|null $price
     * @return $this
     */
    public function setSpecialPrice(?float $price): self
    {
        return $this->setData(self::SPECIAL_PRICE, $price);
    }
}
