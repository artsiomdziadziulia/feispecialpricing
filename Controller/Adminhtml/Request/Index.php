<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Aheadworks_FeiSpecialPricing::requests';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Render special pricing requests grid.
     *
     * @return Page
     */
    public function execute(): Page
    {
        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('Aheadworks_FeiSpecialPricing::requests');
        $page->getConfig()->getTitle()->prepend(__('Special Pricing Requests'));

        return $page;
    }
}
