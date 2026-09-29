<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\FeiBase\Model\Service\Cart\ProductAddRestrictionChecker;
use Aheadworks\FeiSpecialPricing\Api\BasketItemRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterfaceFactory;
use Aheadworks\FeiSpecialPricing\Model\Service\Basket\BuyRequestNormalizer;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class BasketService
{
    /**
     * @param BasketItemRepositoryInterface $basketItemRepository
     * @param BasketItemInterfaceFactory $basketItemFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductAddRestrictionChecker $restrictionChecker
     * @param BuyRequestNormalizer $buyRequestNormalizer
     */
    public function __construct(
        private readonly BasketItemRepositoryInterface $basketItemRepository,
        private readonly BasketItemInterfaceFactory $basketItemFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductAddRestrictionChecker $restrictionChecker,
        private readonly BuyRequestNormalizer $buyRequestNormalizer
    ) {
    }

    /**
     * Add product to the customer's special pricing basket (merges identical configurations).
     *
     * @param int $customerId
     * @param int $productId
     * @param float $qty
     * @param array $buyRequest
     * @return BasketItemInterface
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function add(int $customerId, int $productId, float $qty, array $buyRequest = []): BasketItemInterface
    {
        $this->assertQty($qty);
        $this->assertProductAllowed($productId);

        $options = $this->buyRequestNormalizer->normalize($buyRequest);
        foreach ($this->basketItemRepository->getByCustomerId($customerId) as $item) {
            if ($item->getProductId() === $productId
                && $this->buyRequestNormalizer->isSame($item->getBuyRequest(), $options)
            ) {
                $item->setQty($item->getQty() + $qty);

                return $this->basketItemRepository->save($item);
            }
        }

        $item = $this->basketItemFactory->create();
        $item->setCustomerId($customerId)
            ->setProductId($productId)
            ->setQty($qty)
            ->setBuyRequest($options);

        return $this->basketItemRepository->save($item);
    }

    /**
     * Update qty of a basket item; qty <= 0 removes the item.
     *
     * @param int $customerId
     * @param int $itemId
     * @param float $qty
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws CouldNotDeleteException
     */
    public function updateQty(int $customerId, int $itemId, float $qty): void
    {
        $item = $this->getOwnedItem($customerId, $itemId);
        if ($qty <= 0) {
            $this->basketItemRepository->delete($item);

            return;
        }

        $item->setQty($qty);
        $this->basketItemRepository->save($item);
    }

    /**
     * Remove a basket item.
     *
     * @param int $customerId
     * @param int $itemId
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function remove(int $customerId, int $itemId): void
    {
        $this->basketItemRepository->delete($this->getOwnedItem($customerId, $itemId));
    }

    /**
     * Remove all basket items of a customer.
     *
     * @param int $customerId
     * @return void
     * @throws CouldNotDeleteException
     */
    public function clear(int $customerId): void
    {
        $this->basketItemRepository->deleteByCustomerId($customerId);
    }

    /**
     * Return basket items of a customer.
     *
     * @param int $customerId
     * @return BasketItemInterface[]
     */
    public function getItems(int $customerId): array
    {
        return $this->basketItemRepository->getByCustomerId($customerId);
    }

    /**
     * Load basket item and make sure it belongs to the customer.
     *
     * @param int $customerId
     * @param int $itemId
     * @return BasketItemInterface
     * @throws NoSuchEntityException
     */
    private function getOwnedItem(int $customerId, int $itemId): BasketItemInterface
    {
        $item = $this->basketItemRepository->getById($itemId);
        if ($item->getCustomerId() !== $customerId) {
            throw NoSuchEntityException::singleField(BasketItemInterface::ID, $itemId);
        }

        return $item;
    }

    /**
     * Validate qty value.
     *
     * @param float $qty
     * @return void
     * @throws LocalizedException
     */
    private function assertQty(float $qty): void
    {
        if ($qty <= 0) {
            throw new LocalizedException(__('Please specify a quantity greater than zero.'));
        }
    }

    /**
     * Make sure the product exists, is enabled and not locked for the current customer group.
     *
     * @param int $productId
     * @return void
     * @throws LocalizedException
     */
    private function assertProductAllowed(int $productId): void
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException) {
            throw new LocalizedException(__('The product that was requested does not exist.'));
        }

        if ((int) $product->getStatus() !== ProductStatus::STATUS_ENABLED) {
            throw new LocalizedException(__('The product is not available.'));
        }

        if ($product instanceof Product) {
            $this->restrictionChecker->assertCanAddToCart($product);
        }
    }
}
