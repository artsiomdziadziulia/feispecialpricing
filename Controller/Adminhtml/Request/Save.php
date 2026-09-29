<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Controller\Adminhtml\Request;

use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\AdminComment;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\Composite as PostDataProcessor;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\ExpirationDate;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\ItemPrices;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestDecisionService;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Aheadworks_FeiSpecialPricing::requests';

    public const string DECISION_APPROVE = 'approve';
    public const string DECISION_REJECT = 'reject';

    /**
     * @param Context $context
     * @param PostDataProcessor $postDataProcessor
     * @param RequestDecisionService $decisionService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly PostDataProcessor $postDataProcessor,
        private readonly RequestDecisionService $decisionService,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Approve or reject the request depending on the "decision" param.
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create();
        $requestId = (int) $this->getRequest()->getParam('id');
        $decision = (string) $this->getRequest()->getParam('decision');

        try {
            /** @var \Magento\Framework\App\Request\Http $httpRequest */
            $httpRequest = $this->getRequest();
            $data = $this->postDataProcessor->process((array) $httpRequest->getPostValue());

            match ($decision) {
                self::DECISION_APPROVE => $this->decisionService->approve(
                    $requestId,
                    $data[ItemPrices::KEY] ?? [],
                    $data[ExpirationDate::KEY] ?? null,
                    $data[AdminComment::KEY] ?? null
                ),
                self::DECISION_REJECT => $this->decisionService->reject(
                    $requestId,
                    $data[AdminComment::KEY] ?? null
                ),
                default => throw new LocalizedException(__('Unknown decision.')),
            };

            $this->messageManager->addSuccessMessage(
                $decision === self::DECISION_APPROVE
                    ? __('Request #%1 was approved.', $requestId)
                    : __('Request #%1 was rejected.', $requestId)
            );

            return $redirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->logger->error('FEI Special Pricing: admin decision failed', ['exception' => $exception]);
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the decision.'));
        }

        return $redirect->setPath('*/*/edit', ['id' => $requestId]);
    }
}
