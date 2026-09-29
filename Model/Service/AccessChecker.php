<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\Ca\Api\AuthorizationManagementInterface;
use Aheadworks\FeiCa\Model\RestrictionBypassChecker;

class AccessChecker
{
    public const string ACL_RESOURCE = 'Aheadworks_FeiSpecialPricing::special_pricing';

    /**
     * @param CurrentCompanyUserProvider $companyUserProvider
     * @param AuthorizationManagementInterface $authorizationManagement
     * @param RestrictionBypassChecker $restrictionBypassChecker
     */
    public function __construct(
        private readonly CurrentCompanyUserProvider $companyUserProvider,
        private readonly AuthorizationManagementInterface $authorizationManagement,
        private readonly RestrictionBypassChecker $restrictionBypassChecker
    ) {
    }

    /**
     * Check whether the logged-in user may use Special Pricing.
     *
     * @return bool
     */
    public function canUse(): bool
    {
        return $this->companyUserProvider->getCompanyId() !== null
            && $this->authorizationManagement->isAllowedByResource(self::ACL_RESOURCE);
    }

    /**
     * Check whether the logged-in user sees all company requests (Master Admin / Agency Administrator).
     *
     * @return bool
     */
    public function isCompanyAdmin(): bool
    {
        return $this->restrictionBypassChecker->shouldBypass();
    }
}
