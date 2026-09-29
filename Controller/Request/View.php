<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Request;

use Aheadworks\FeiSpecialPricing\Controller\AbstractSpecialPricingAction;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\ViewModel\Request\View as RequestViewModel;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class View extends AbstractSpecialPricingAction implements HttpGetActionInterface
{
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param AccessChecker $accessChecker
     * @param PageFactory $resultPageFactory
     * @param RequestViewModel $requestViewModel
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        AccessChecker $accessChecker,
        private readonly PageFactory $resultPageFactory,
        private readonly RequestViewModel $requestViewModel
    ) {
        parent::__construct($context, $customerSession, $accessChecker);
    }

    /**
     * Render special pricing request details.
     *
     * @return Page
     * @throws NotFoundException
     */
    public function execute(): Page
    {
        $request = $this->requestViewModel->getRequest();
        if ($request === null) {
            throw new NotFoundException(__('Page not found.'));
        }

        $page = $this->resultPageFactory->create();
        $page->getConfig()->getTitle()->set(__('Special Pricing Request #%1', $request->getId()));
        $this->setBackLink($page, $this->_url->getUrl('aw_fei_sp/request/index'));

        return $page;
    }
}
