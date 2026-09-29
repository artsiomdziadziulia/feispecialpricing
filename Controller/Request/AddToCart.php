<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Request;

use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Controller\AbstractSpecialPricingAction;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestToCartService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class AddToCart extends AbstractSpecialPricingAction implements HttpPostActionInterface
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param AccessChecker $accessChecker
     * @param RequestRepositoryInterface $requestRepository
     * @param RequestToCartService $requestToCartService
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        AccessChecker $accessChecker,
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly RequestToCartService $requestToCartService,
        private readonly CheckoutSession $checkoutSession,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context, $customerSession, $accessChecker);
    }

    /**
     * Put approved items into the cart at the approved prices.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $requestId = (int) $this->getRequest()->getParam('id');

        try {
            $request = $this->requestRepository->getById($requestId);
            $this->requestToCartService->addToCart(
                $request,
                $this->checkoutSession->getQuote(),
                $this->getCustomerId()
            );
            $this->messageManager->addSuccessMessage(
                __('Items from special pricing request #%1 were added to your cart.', $requestId)
            );

            return $redirect->setPath('checkout/cart');
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('The request was not found.'));

            return $redirect->setPath('aw_fei_sp/request/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error('FEI Special Pricing: add to cart failed', ['exception' => $exception]);
            $this->messageManager->addErrorMessage(__('We cannot add the items to your cart right now.'));
        }

        return $redirect->setPath('aw_fei_sp/request/view', ['id' => $requestId]);
    }
}
