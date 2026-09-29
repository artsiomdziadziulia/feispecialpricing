<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Observer\Quote;

use Aheadworks\FeiSpecialPricing\Model\Service\Cart\LinkedItemValidator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class ValidateBeforeSubmitObserver implements ObserverInterface
{
    /**
     * @param LinkedItemValidator $validator
     */
    public function __construct(
        private readonly LinkedItemValidator $validator
    ) {
    }

    /**
     * Stop order placement when a special-priced line is invalid.
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getData('quote');
        if (!$quote instanceof Quote) {
            return;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            $violation = $this->validator->getViolation($item);
            if ($violation !== null) {
                throw new LocalizedException($violation);
            }
        }
    }
}
