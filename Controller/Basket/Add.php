<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Basket;

use Aheadworks\FeiSpecialPricing\Controller\AbstractSpecialPricingAction;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\BasketService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Add extends AbstractSpecialPricingAction implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param AccessChecker $accessChecker
     * @param BasketService $basketService
     * @param JsonFactory $jsonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        AccessChecker $accessChecker,
        private readonly BasketService $basketService,
        private readonly JsonFactory $jsonFactory,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context, $customerSession, $accessChecker);
    }

    /**
     * Add product (with selected options) to the Special Pricing basket.
     *
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $params = $this->getRequest()->getParams();
        $productId = (int) ($params['product'] ?? 0);
        $qty = (float) ($params['qty'] ?? 1);

        try {
            $this->basketService->add($this->getCustomerId(), $productId, $qty, $params);
            $count = count($this->basketService->getItems($this->getCustomerId()));

            return $result->setData([
                'success' => true,
                'message' => (string) __('The product was added to your Special Pricing basket.'),
                'count' => $count,
            ]);
        } catch (LocalizedException $exception) {
            return $result->setData(['success' => false, 'message' => $exception->getMessage()]);
        } catch (\Exception $exception) {
            $this->logger->error('FEI Special Pricing: add to basket failed', ['exception' => $exception]);

            return $result->setData([
                'success' => false,
                'message' => (string) __('We cannot add this product to the Special Pricing basket right now.'),
            ]);
        }
    }
}
