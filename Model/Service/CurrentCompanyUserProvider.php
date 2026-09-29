<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\Ca\Api\CompanyUserManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;

class CurrentCompanyUserProvider
{
    /**
     * @param CompanyUserManagementInterface $companyUserManagement
     */
    public function __construct(
        private readonly CompanyUserManagementInterface $companyUserManagement
    ) {
    }

    /**
     * Return logged-in customer when it belongs to a company.
     *
     * @return CustomerInterface|null
     */
    public function getCustomer(): ?CustomerInterface
    {
        $customer = $this->companyUserManagement->getCurrentUser();
        if (!$customer instanceof CustomerInterface) {
            return null;
        }

        return $this->getCompanyIdOf($customer) !== null ? $customer : null;
    }

    /**
     * Return logged-in company user ID.
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $customer = $this->getCustomer();

        return $customer !== null ? (int) $customer->getId() : null;
    }

    /**
     * Return company ID of the logged-in user.
     *
     * @return int|null
     */
    public function getCompanyId(): ?int
    {
        $customer = $this->getCustomer();

        return $customer !== null ? $this->getCompanyIdOf($customer) : null;
    }

    /**
     * Extract company ID from the customer extension attributes.
     *
     * @param CustomerInterface $customer
     * @return int|null
     */
    public function getCompanyIdOf(CustomerInterface $customer): ?int
    {
        $companyId = (int) $customer->getExtensionAttributes()?->getAwCaCompanyUser()?->getCompanyId();

        return $companyId > 0 ? $companyId : null;
    }
}
