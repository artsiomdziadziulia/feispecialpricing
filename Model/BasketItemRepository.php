<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Aheadworks\FeiSpecialPricing\Api\BasketItemRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\BasketItem as BasketItemResource;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\BasketItem\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BasketItemRepository implements BasketItemRepositoryInterface
{
    /**
     * @param BasketItemResource $resource
     * @param BasketItemFactory $factory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly BasketItemResource $resource,
        private readonly BasketItemFactory $factory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Save basket item.
     *
     * @param BasketItemInterface $item
     * @return BasketItemInterface
     * @throws CouldNotSaveException
     */
    public function save(BasketItemInterface $item): BasketItemInterface
    {
        try {
            /** @var BasketItem $item */
            $this->resource->save($item);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Unable to save the special pricing basket item.'), $exception);
        }

        return $item;
    }

    /**
     * Load basket item by ID.
     *
     * @param int $id
     * @return BasketItemInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): BasketItemInterface
    {
        $item = $this->factory->create();
        $this->resource->load($item, $id);
        if (!$item->getId()) {
            throw NoSuchEntityException::singleField(BasketItemInterface::ID, $id);
        }

        return $item;
    }

    /**
     * Return all basket items of a customer.
     *
     * @param int $customerId
     * @return BasketItemInterface[]
     */
    public function getByCustomerId(int $customerId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(BasketItemInterface::CUSTOMER_ID, ['eq' => $customerId]);
        $collection->setOrder(BasketItemInterface::ID, 'ASC');
        /** @var BasketItemInterface[] $items */
        $items = array_values($collection->getItems());

        return $items;
    }

    /**
     * Delete basket item.
     *
     * @param BasketItemInterface $item
     * @return void
     * @throws CouldNotDeleteException
     */
    public function delete(BasketItemInterface $item): void
    {
        try {
            /** @var BasketItem $item */
            $this->resource->delete($item);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Unable to delete the special pricing basket item.'), $exception);
        }
    }

    /**
     * Delete all basket items of a customer.
     *
     * @param int $customerId
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteByCustomerId(int $customerId): void
    {
        try {
            $this->resource->deleteByCustomerId($customerId);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Unable to clear the special pricing basket.'), $exception);
        }
    }
}
