<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Observer\Quote;

use Aheadworks\FeiSpecialPricing\Model\Service\Cart\LinkedItemValidator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Item\AbstractItem;

class ValidateLinkedItemQtyObserver implements ObserverInterface
{
    /**
     * @param LinkedItemValidator $validator
     */
    public function __construct(
        private readonly LinkedItemValidator $validator
    ) {
    }

    /**
     * Block raising qty of a special-priced line above the approved qty.
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $item = $observer->getEvent()->getData('item');
        if (!$item instanceof AbstractItem) {
            return;
        }

        $violation = $this->validator->getQtyViolation($item);
        if ($violation !== null) {
            throw new LocalizedException($violation);
        }
    }
}
