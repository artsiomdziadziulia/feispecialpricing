<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request as RequestResource;
use Magento\Framework\Model\AbstractModel;

class Request extends AbstractModel implements RequestInterface
{
    /**
     * Initialize model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(RequestResource::class);
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
     * Return company ID.
     *
     * @return int
     */
    public function getCompanyId(): int
    {
        return (int) $this->getData(self::COMPANY_ID);
    }

    /**
     * Set company ID.
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId(int $companyId): self
    {
        return $this->setData(self::COMPANY_ID, $companyId);
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
     * Return store ID.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int) $this->getData(self::STORE_ID);
    }

    /**
     * Set store ID.
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Return status code.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return (string) ($this->getData(self::STATUS) ?: Status::Pending->value);
    }

    /**
     * Set status code.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Return customer comment.
     *
     * @return string|null
     */
    public function getCustomerComment(): ?string
    {
        $value = $this->getData(self::CUSTOMER_COMMENT);

        return $value !== null ? (string) $value : null;
    }

    /**
     * Set customer comment.
     *
     * @param string|null $comment
     * @return $this
     */
    public function setCustomerComment(?string $comment): self
    {
        return $this->setData(self::CUSTOMER_COMMENT, $comment);
    }

    /**
     * Return FEI comment.
     *
     * @return string|null
     */
    public function getAdminComment(): ?string
    {
        $value = $this->getData(self::ADMIN_COMMENT);

        return $value !== null ? (string) $value : null;
    }

    /**
     * Set FEI comment.
     *
     * @param string|null $comment
     * @return $this
     */
    public function setAdminComment(?string $comment): self
    {
        return $this->setData(self::ADMIN_COMMENT, $comment);
    }

    /**
     * Return expiration datetime.
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        $value = $this->getData(self::EXPIRES_AT);

        return $value !== null && $value !== '' ? (string) $value : null;
    }

    /**
     * Set expiration datetime.
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setExpiresAt(?string $expiresAt): self
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    /**
     * Return order ID.
     *
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        $value = $this->getData(self::ORDER_ID);

        return $value !== null ? (int) $value : null;
    }

    /**
     * Set order ID.
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId): self
    {
        return $this->setData(self::ORDER_ID, $orderId);
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

    /**
     * Return updated at timestamp.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        $value = $this->getData(self::UPDATED_AT);

        return $value !== null ? (string) $value : null;
    }

    /**
     * Return request items.
     *
     * @return \Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface[]
     */
    public function getItems(): array
    {
        $items = $this->getData(self::ITEMS);

        return is_array($items) ? $items : [];
    }

    /**
     * Set request items.
     *
     * @param \Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self
    {
        return $this->setData(self::ITEMS, array_values($items));
    }
}
