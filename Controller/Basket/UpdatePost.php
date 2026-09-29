<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Basket;

use Aheadworks\FeiSpecialPricing\Controller\AbstractSpecialPricingAction;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\BasketService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class UpdatePost extends AbstractSpecialPricingAction implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param AccessChecker $accessChecker
     * @param BasketService $basketService
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        AccessChecker $accessChecker,
        private readonly BasketService $basketService
    ) {
        parent::__construct($context, $customerSession, $accessChecker);
    }

    /**
     * Update basket qtys or remove a single item.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $customerId = $this->getCustomerId();
        $removeId = (int) $this->getRequest()->getParam('remove');
        $qtys = (array) $this->getRequest()->getParam('qty', []);

        try {
            if ($removeId > 0) {
                $this->basketService->remove($customerId, $removeId);
                $this->messageManager->addSuccessMessage(__('The item was removed from the basket.'));
            } else {
                foreach ($qtys as $itemId => $qty) {
                    $this->basketService->updateQty($customerId, (int) $itemId, (float) $qty);
                }
                $this->messageManager->addSuccessMessage(__('The basket was updated.'));
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('aw_fei_sp/basket/index');
    }
}
