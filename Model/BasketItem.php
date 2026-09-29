<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\BasketItem as BasketItemResource;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\SerializerInterface;

class BasketItem extends AbstractModel implements BasketItemInterface
{
    use BuyRequestDataTrait;

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
        $this->_init(BasketItemResource::class);
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
     * Return customer ID.
     *
     * @return int
     */
    public function getCustomerId(): int
    {
        return (int) $this->getData(self::CUSTOMER_ID);
    }

    /**
     * Set customer ID.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Return product ID.
     *
     * @return int
     */
    public function getProductId(): int
    {
        return (int) $this->getData(self::PRODUCT_ID);
    }

    /**
     * Set product ID.
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * Return requested qty.
     *
     * @return float
     */
    public function getQty(): float
    {
        return (float) $this->getData(self::QTY);
    }

    /**
     * Set requested qty.
     *
     * @param float $qty
     * @return $this
     */
    public function setQty(float $qty): self
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * Return created at timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        $value = $this->getData(self::CREATED_AT);

        return $value !== null ? (string) $value : null;
    }
}
