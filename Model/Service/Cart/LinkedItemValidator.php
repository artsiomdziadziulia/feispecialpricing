<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Cart;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestAvailabilityChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestToCartService;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class LinkedItemValidator
{
    private const float QTY_PRECISION = 0.0001;

    /**
     * @param RequestRepositoryInterface $requestRepository
     * @param RequestAvailabilityChecker $availabilityChecker
     */
    public function __construct(
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly RequestAvailabilityChecker $availabilityChecker
    ) {
    }

    /**
     * Return linked request item ID of the quote line (0 when not linked).
     *
     * @param AbstractItem $item
     * @return int
     */
    public function getLinkId(AbstractItem $item): int
    {
        return (int) $item->getData(RequestToCartService::ITEM_LINK_FIELD);
    }

    /**
     * Return error when approved prices can no longer be used for the line.
     *
     * @param AbstractItem $item
     * @return Phrase|null
     */
    public function getAvailabilityViolation(AbstractItem $item): ?Phrase
    {
        $linkId = $this->getLinkId($item);
        if ($linkId === 0) {
            return null;
        }

        $requestItem = $this->loadRequestItem($linkId);
        if ($requestItem === null) {
            return __('The approved special price for "%1" is no longer available.', $item->getName());
        }

        try {
            $request = $this->requestRepository->getById($requestItem->getRequestId());
        } catch (NoSuchEntityException) {
            return __('The approved special price for "%1" is no longer available.', $item->getName());
        }

        $customerId = (int) $item->getQuote()->getCustomerId();
        if (!$this->availabilityChecker->isPurchasable($request, $customerId)) {
            return __('The approved special price for "%1" is no longer available.', $item->getName());
        }

        return null;
    }

    /**
     * Return error when the line qty exceeds the approved qty.
     *
     * @param AbstractItem $item
     * @return Phrase|null
     */
    public function getQtyViolation(AbstractItem $item): ?Phrase
    {
        $linkId = $this->getLinkId($item);
        if ($linkId === 0) {
            return null;
        }

        $requestItem = $this->loadRequestItem($linkId);
        if ($requestItem === null) {
            return null;
        }

        if ((float) $item->getQty() - $requestItem->getQty() > self::QTY_PRECISION) {
            return __(
                'You can buy up to %1 of "%2" at the approved special price.',
                $requestItem->getQty() + 0,
                $item->getName()
            );
        }

        return null;
    }

    /**
     * Return first violation (availability or qty) of the line.
     *
     * @param AbstractItem $item
     * @return Phrase|null
     */
    public function getViolation(AbstractItem $item): ?Phrase
    {
        return $this->getAvailabilityViolation($item) ?? $this->getQtyViolation($item);
    }

    /**
     * Load request item, null when missing.
     *
     * @param int $itemId
     * @return RequestItemInterface|null
     */
    private function loadRequestItem(int $itemId): ?RequestItemInterface
    {
        try {
            return $this->requestRepository->getItemById($itemId);
        } catch (NoSuchEntityException) {
            return null;
        }
    }
}
