<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Observer\Order;

use Aheadworks\FeiSpecialPricing\Model\Service\RequestOrderLinker;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class MarkRequestOrderedObserver implements ObserverInterface
{
    /**
     * @param RequestOrderLinker $requestOrderLinker
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestOrderLinker $requestOrderLinker,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Close requests used in the placed order.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getData('order');
        if (!$order instanceof OrderInterface) {
            return;
        }

        try {
            $this->requestOrderLinker->markOrdered($order);
        } catch (\Exception $exception) {
            $this->logger->error(
                'FEI Special Pricing: unable to mark request as ordered for order ' . $order->getIncrementId(),
                ['exception' => $exception]
            );
        }
    }
}
