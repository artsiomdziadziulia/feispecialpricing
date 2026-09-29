<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api\Data;

interface RequestInterface
{
    public const string ID = 'id';
    public const string COMPANY_ID = 'company_id';
    public const string CUSTOMER_ID = 'customer_id';
    public const string STORE_ID = 'store_id';
    public const string STATUS = 'status';
    public const string CUSTOMER_COMMENT = 'customer_comment';
    public const string ADMIN_COMMENT = 'admin_comment';
    public const string EXPIRES_AT = 'expires_at';
    public const string ORDER_ID = 'order_id';
    public const string CREATED_AT = 'created_at';
    public const string UPDATED_AT = 'updated_at';
    public const string ITEMS = 'items';

    /**
     * Return entity ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Return company ID.
     *
     * @return int
     */
    public function getCompanyId(): int;

    /**
     * Set company ID.
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId(int $companyId): self;

    /**
     * Return customer ID of the requester.
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
     * Return store ID.
     *
     * @return int
     */
    public function getStoreId(): int;

    /**
     * Set store ID.
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): self;

    /**
     * Return status code (see \Aheadworks\FeiSpecialPricing\Model\Request\Status).
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set status code.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Return customer comment.
     *
     * @return string|null
     */
    public function getCustomerComment(): ?string;

    /**
     * Set customer comment.
     *
     * @param string|null $comment
     * @return $this
     */
    public function setCustomerComment(?string $comment): self;

    /**
     * Return FEI comment.
     *
     * @return string|null
     */
    public function getAdminComment(): ?string;

    /**
     * Set FEI comment.
     *
     * @param string|null $comment
     * @return $this
     */
    public function setAdminComment(?string $comment): self;

    /**
     * Return expiration datetime of approved prices (UTC, Y-m-d H:i:s).
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string;

    /**
     * Set expiration datetime.
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setExpiresAt(?string $expiresAt): self;

    /**
     * Return order ID placed with approved prices.
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Set order ID.
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId): self;

    /**
     * Return created at timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Return updated at timestamp.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Return request items.
     *
     * @return \Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface[]
     */
    public function getItems(): array;

    /**
     * Set request items.
     *
     * @param \Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items): self;
}
