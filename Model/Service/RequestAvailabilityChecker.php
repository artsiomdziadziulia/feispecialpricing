<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Framework\Stdlib\DateTime\DateTime;

class RequestAvailabilityChecker
{
    /**
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Check whether approved prices of the request can still be used by the customer.
     *
     * Approved prices are personal: only the requester can buy with them, once.
     *
     * @param RequestInterface $request
     * @param int $customerId
     * @return bool
     */
    public function isPurchasable(RequestInterface $request, int $customerId): bool
    {
        return $request->getCustomerId() === $customerId
            && $request->getStatus() === Status::Approved->value
            && !$this->isExpired($request);
    }

    /**
     * Check whether the request validity period has passed.
     *
     * @param RequestInterface $request
     * @return bool
     */
    public function isExpired(RequestInterface $request): bool
    {
        $expiresAt = $request->getExpiresAt();
        if ($expiresAt === null) {
            return false;
        }

        return strtotime($expiresAt . ' UTC') <= $this->dateTime->gmtTimestamp();
    }

    /**
     * Check whether a company user may view the request.
     *
     * @param RequestInterface $request
     * @param int $customerId
     * @param int|null $companyId
     * @param bool $isCompanyAdmin Master Admin or Agency Administrator
     * @return bool
     */
    public function canView(RequestInterface $request, int $customerId, ?int $companyId, bool $isCompanyAdmin): bool
    {
        if ($companyId === null || $request->getCompanyId() !== $companyId) {
            return false;
        }

        return $isCompanyAdmin || $request->getCustomerId() === $customerId;
    }
}
