<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller;

use Aheadworks\Ca\Controller\AbstractCustomerAction;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\NotFoundException;

abstract class AbstractSpecialPricingAction extends AbstractCustomerAction
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param AccessChecker $accessChecker
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        protected readonly AccessChecker $accessChecker
    ) {
        parent::__construct($context, $customerSession);
    }

    /**
     * Allow only authenticated company users with the Special Pricing permission.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    #[\Override]
    public function dispatch(RequestInterface $request)
    {
        if ($this->customerSession->isLoggedIn() && !$this->accessChecker->canUse()) {
            throw new NotFoundException(__('Page not found.'));
        }

        return parent::dispatch($request);
    }

    /**
     * Ownership is validated by each action through services.
     *
     * @return bool
     */
    protected function isEntityBelongsToCustomer(): bool
    {
        return true;
    }

    /**
     * Return logged-in customer ID.
     *
     * @return int
     */
    protected function getCustomerId(): int
    {
        return (int) $this->customerSession->getCustomerId();
    }
}
