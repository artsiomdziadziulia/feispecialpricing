<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Basket;

use Aheadworks\FeiSpecialPricing\Controller\AbstractSpecialPricingAction;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestSubmitService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Submit extends AbstractSpecialPricingAction implements HttpPostActionInterface
{
    private const int COMMENT_MAX_LENGTH = 2000;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param AccessChecker $accessChecker
     * @param RequestSubmitService $requestSubmitService
     * @param CurrentCompanyUserProvider $companyUserProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        AccessChecker $accessChecker,
        private readonly RequestSubmitService $requestSubmitService,
        private readonly CurrentCompanyUserProvider $companyUserProvider,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context, $customerSession, $accessChecker);
    }

    /**
     * Submit the basket as a special pricing request to FEI.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $customer = $this->companyUserProvider->getCustomer();
        if ($customer === null) {
            return $redirect->setPath('aw_fei_sp/basket/index');
        }

        $comment = mb_substr(
            trim((string) $this->getRequest()->getParam('comment')),
            0,
            self::COMMENT_MAX_LENGTH
        );

        try {
            $request = $this->requestSubmitService->submit($customer, $comment);
            $this->messageManager->addSuccessMessage(
                __('Your special pricing request #%1 was sent to FEI for approval.', $request->getId())
            );

            return $redirect->setPath('aw_fei_sp/request/view', ['id' => $request->getId()]);
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error('FEI Special Pricing: submit failed', ['exception' => $exception]);
            $this->messageManager->addErrorMessage(__('We cannot submit your request right now.'));
        }

        return $redirect->setPath('aw_fei_sp/basket/index');
    }
}
