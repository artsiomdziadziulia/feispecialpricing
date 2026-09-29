<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Adminhtml\Request;

use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Aheadworks_FeiSpecialPricing::requests';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequestRepositoryInterface $requestRepository
     */
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RequestRepositoryInterface $requestRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Render request review form.
     *
     * @return Page|Redirect
     */
    public function execute(): Page|Redirect
    {
        $requestId = (int) $this->getRequest()->getParam('id');
        try {
            $this->requestRepository->getById($requestId);
        } catch (NoSuchEntityException) {
            $this->messageManager->addErrorMessage(__('This request no longer exists.'));

            return $this->resultRedirectFactory->create()->setPath('*/*/index');
        }

        /** @var Page $page */
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('Aheadworks_FeiSpecialPricing::requests');
        $page->getConfig()->getTitle()->prepend(__('Special Pricing Request #%1', $requestId));

        return $page;
    }
}
