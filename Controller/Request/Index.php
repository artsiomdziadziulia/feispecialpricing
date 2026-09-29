<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Request;

use Aheadworks\FeiSpecialPricing\Controller\AbstractSpecialPricingAction;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends AbstractSpecialPricingAction implements HttpGetActionInterface
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param AccessChecker $accessChecker
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        AccessChecker $accessChecker,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $customerSession, $accessChecker);
    }

    /**
     * Render the list of special pricing requests.
     *
     * @return Page
     */
    public function execute(): Page
    {
        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->set(__('Special Pricing Requests'));

        return $page;
    }
}
