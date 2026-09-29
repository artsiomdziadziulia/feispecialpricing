<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Block\Adminhtml\Request\Edit;

use Aheadworks\FeiSpecialPricing\Controller\Adminhtml\Request\Save;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class ApproveButton implements ButtonProviderInterface
{
    /**
     * @param DecisionButtonContext $context
     */
    public function __construct(
        private readonly DecisionButtonContext $context
    ) {
    }

    /**
     * Return "Approve" button config.
     *
     * @return array
     */
    public function getButtonData(): array
    {
        return $this->context->buildButton((string) __('Approve'), Save::DECISION_APPROVE, 'save primary', 30);
    }
}
