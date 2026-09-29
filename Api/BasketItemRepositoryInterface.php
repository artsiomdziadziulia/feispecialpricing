<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api;

use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface BasketItemRepositoryInterface
{
    /**
     * Save basket item.
     *
     * @param BasketItemInterface $item
     * @return BasketItemInterface
     * @throws CouldNotSaveException
     */
    public function save(BasketItemInterface $item): BasketItemInterface;

    /**
     * Load basket item by ID.
     *
     * @param int $id
     * @return BasketItemInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): BasketItemInterface;

    /**
     * Return all basket items of a customer.
     *
     * @param int $customerId
     * @return BasketItemInterface[]
     */
    public function getByCustomerId(int $customerId): array;

    /**
     * Delete basket item.
     *
     * @param BasketItemInterface $item
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(BasketItemInterface $item): void;

    /**
     * Delete all basket items of a customer.
     *
     * @param int $customerId
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteByCustomerId(int $customerId): void;
}
