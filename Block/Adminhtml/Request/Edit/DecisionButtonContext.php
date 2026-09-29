<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Block\Adminhtml\Request\Edit;

use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Framework\App\RequestInterface as HttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;

class DecisionButtonContext
{
    /**
     * @param HttpRequest $httpRequest
     * @param RequestRepositoryInterface $requestRepository
     */
    public function __construct(
        private readonly HttpRequest $httpRequest,
        private readonly RequestRepositoryInterface $requestRepository
    ) {
    }

    /**
     * Check whether the edited request can still be approved or rejected.
     *
     * @return bool
     */
    public function isDecidable(): bool
    {
        try {
            $request = $this->requestRepository->getById((int) $this->httpRequest->getParam('id'));
        } catch (NoSuchEntityException) {
            return false;
        }

        return in_array($request->getStatus(), [Status::Pending->value, Status::Approved->value], true);
    }

    /**
     * Build button config that submits the form with a decision.
     *
     * @param string $label
     * @param string $decision
     * @param string $class
     * @param int $sortOrder
     * @return array
     */
    public function buildButton(string $label, string $decision, string $class, int $sortOrder): array
    {
        if (!$this->isDecidable()) {
            return [];
        }

        return [
            'label' => $label,
            'class' => $class,
            'sort_order' => $sortOrder,
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'aw_fei_sp_request_form.aw_fei_sp_request_form',
                                'actionName' => 'save',
                                'params' => [true, ['decision' => $decision]],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
