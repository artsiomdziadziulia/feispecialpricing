<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Block\Account;

use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Magento\Framework\App\DefaultPathInterface;
use Magento\Framework\View\Element\Html\Link\Current;
use Magento\Framework\View\Element\Template\Context;

class NavigationLink extends Current
{
    /**
     * @param Context $context
     * @param DefaultPathInterface $defaultPath
     * @param AccessChecker $accessChecker
     * @param array $data
     */
    public function __construct(
        Context $context,
        DefaultPathInterface $defaultPath,
        private readonly AccessChecker $accessChecker,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
    }

    /**
     * Render link only for users allowed to use Special Pricing.
     *
     * @return string
     */
    #[\Override]
    protected function _toHtml(): string
    {
        if (!$this->accessChecker->canUse()) {
            return '';
        }

        return parent::_toHtml();
    }
}
