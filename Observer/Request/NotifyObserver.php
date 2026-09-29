<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Observer\Request;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Email\Notifier;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestSubmitService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class NotifyObserver implements ObserverInterface
{
    /**
     * @param Notifier $notifier
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Notifier $notifier,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send email for a submitted or decided request; mail failures never break the flow.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $request = $observer->getEvent()->getData('request');
        if (!$request instanceof RequestInterface) {
            return;
        }

        try {
            if ($observer->getEvent()->getName() === RequestSubmitService::EVENT_SUBMITTED) {
                $this->notifier->notifyNewRequest($request);
            } else {
                $this->notifier->notifyDecision($request);
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'FEI Special Pricing: notification failed for request #' . $request->getId(),
                ['exception' => $exception]
            );
        }
    }
}
