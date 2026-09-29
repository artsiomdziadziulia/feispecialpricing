<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Model\BuyRequestAwareInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;

class RequestToCartService
{
    public const string ITEM_LINK_FIELD = 'aw_fei_sp_request_item_id';

    /**
     * @param RequestAvailabilityChecker $availabilityChecker
     * @param ProductRepositoryInterface $productRepository
     * @param CartRepositoryInterface $cartRepository
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        private readonly RequestAvailabilityChecker $availabilityChecker,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly DataObjectFactory $dataObjectFactory
    ) {
    }

    /**
     * Add approved request items to the cart with locked custom prices.
     *
     * Previously added lines of the same request are replaced, so the approved qty is never exceeded.
     *
     * @param RequestInterface $request
     * @param Quote $quote
     * @param int $customerId
     * @return int Number of added lines
     * @throws LocalizedException
     */
    public function addToCart(RequestInterface $request, Quote $quote, int $customerId): int
    {
        if (!$this->availabilityChecker->isPurchasable($request, $customerId)) {
            throw new LocalizedException(__('The approved prices of this request are no longer available.'));
        }

        $this->removeLinkedItems($quote, $request);

        $added = 0;
        foreach ($request->getItems() as $requestItem) {
            if ($requestItem->getProductId() === null || $requestItem->getSpecialPrice() === null) {
                continue;
            }
            $this->addItem($quote, $requestItem, $request->getStoreId());
            $added++;
        }

        if ($added === 0) {
            throw new LocalizedException(__('None of the requested products are available anymore.'));
        }

        $quote->collectTotals();
        $this->cartRepository->save($quote);

        return $added;
    }

    /**
     * Remove quote lines already linked to items of this request.
     *
     * @param Quote $quote
     * @param RequestInterface $request
     * @return void
     */
    public function removeLinkedItems(Quote $quote, RequestInterface $request): void
    {
        $itemIds = array_map(
            static fn (RequestItemInterface $item): int => (int) $item->getId(),
            $request->getItems()
        );

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if (in_array((int) $quoteItem->getData(self::ITEM_LINK_FIELD), $itemIds, true)) {
                $quote->removeItem($quoteItem->getId());
            }
        }
    }

    /**
     * Add a single request item to the quote and lock its price.
     *
     * @param Quote $quote
     * @param RequestItemInterface $requestItem
     * @param int $storeId
     * @return void
     * @throws LocalizedException
     */
    private function addItem(Quote $quote, RequestItemInterface $requestItem, int $storeId): void
    {
        try {
            /** @var Product $product */
            $product = $this->productRepository->getById((int) $requestItem->getProductId(), false, $storeId, true);
        } catch (NoSuchEntityException) {
            throw new LocalizedException(__('Product "%1" is no longer available.', $requestItem->getSku()));
        }

        // Unique option keeps the line separate from regular cart lines of the same product.
        $product->addCustomOption(self::ITEM_LINK_FIELD, (string) $requestItem->getId());

        $options = $requestItem instanceof BuyRequestAwareInterface ? $requestItem->getBuyRequest() : [];
        $buyRequest = $this->dataObjectFactory->create([
            'data' => array_merge($options, ['qty' => $requestItem->getQty()]),
        ]);

        $result = $quote->addProduct($product, $buyRequest);
        if (!$result instanceof QuoteItem) {
            throw new LocalizedException(
                __('Product "%1" cannot be added to the cart: %2', $requestItem->getSku(), (string) $result)
            );
        }

        $price = (float) $requestItem->getSpecialPrice();
        $result->setCustomPrice($price);
        $result->setOriginalCustomPrice($price);
        $result->setData(self::ITEM_LINK_FIELD, (int) $requestItem->getId());
        $result->getProduct()->setIsSuperMode(true);
    }
}
