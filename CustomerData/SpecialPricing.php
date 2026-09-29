<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\CustomerData;

use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\BasketService;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use Magento\Customer\CustomerData\SectionSourceInterface;

class SpecialPricing implements SectionSourceInterface
{
    /**
     * @param AccessChecker $accessChecker
     * @param CurrentCompanyUserProvider $companyUserProvider
     * @param BasketService $basketService
     */
    public function __construct(
        private readonly AccessChecker $accessChecker,
        private readonly CurrentCompanyUserProvider $companyUserProvider,
        private readonly BasketService $basketService
    ) {
    }

    /**
     * Return private data for FPC-safe rendering of Special Pricing controls.
     *
     * @return array{can_use: bool, basket_count: int}
     */
    public function getSectionData(): array
    {
        if (!$this->accessChecker->canUse()) {
            return ['can_use' => false, 'basket_count' => 0];
        }

        $customerId = (int) $this->companyUserProvider->getCustomerId();

        return [
            'can_use' => true,
            'basket_count' => count($this->basketService->getItems($customerId)),
        ];
    }
}
