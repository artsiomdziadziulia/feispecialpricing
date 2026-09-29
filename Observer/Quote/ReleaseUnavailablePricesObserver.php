<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Observer\Quote;

use Aheadworks\FeiSpecialPricing\Model\Service\Cart\LinkedItemValidator;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestToCartService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;

class ReleaseUnavailablePricesObserver implements ObserverInterface
{
    /**
     * @param LinkedItemValidator $validator
     */
    public function __construct(
        private readonly LinkedItemValidator $validator
    ) {
    }

    /**
     * Drop locked custom price from lines whose request is no longer purchasable.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getData('quote');
        if (!$quote instanceof Quote || !$quote->getIsActive()) {
            return;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($this->validator->getLinkId($item) === 0
                || $this->validator->getAvailabilityViolation($item) === null
            ) {
                continue;
            }

            $item->setData('custom_price', null);
            $item->setData('original_custom_price', null);
            $item->setData(RequestToCartService::ITEM_LINK_FIELD, null);
        }
    }
}
