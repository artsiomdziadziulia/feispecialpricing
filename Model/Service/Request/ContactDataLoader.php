<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Request;

use Aheadworks\Ca\Api\CompanyRepositoryInterface;
use Aheadworks\Ca\Api\Data\CompanyInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

class ContactDataLoader
{
    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * Set requester name / email and agency name on requests, one query per entity type.
     *
     * @param RequestInterface[] $requests
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function load(array $requests): void
    {
        if ($requests === []) {
            return;
        }

        $customers = $this->loadCustomers($requests);
        $companies = $this->loadCompanies($requests);

        foreach ($requests as $request) {
            $customer = $customers[$request->getCustomerId()] ?? null;
            $request->setCustomerName($customer['name'] ?? null)
                ->setCustomerEmail($customer['email'] ?? null)
                ->setCompanyName($companies[$request->getCompanyId()] ?? null);
        }
    }

    /**
     * Return customer name / email indexed by customer ID.
     *
     * @param RequestInterface[] $requests
     * @return array<int, array{name: string, email: string}>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loadCustomers(array $requests): array
    {
        $ids = array_unique(array_map(static fn (RequestInterface $r): int => $r->getCustomerId(), $requests));
        $criteria = $this->searchCriteriaBuilder->addFilter('entity_id', $ids, 'in')->create();

        $result = [];
        foreach ($this->customerRepository->getList($criteria)->getItems() as $customer) {
            $result[(int) $customer->getId()] = [
                'name' => trim($customer->getFirstname() . ' ' . $customer->getLastname()),
                'email' => (string) $customer->getEmail(),
            ];
        }

        return $result;
    }

    /**
     * Return company names indexed by company ID.
     *
     * @param RequestInterface[] $requests
     * @return array<int, string>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function loadCompanies(array $requests): array
    {
        $ids = array_unique(array_map(static fn (RequestInterface $r): int => $r->getCompanyId(), $requests));
        $criteria = $this->searchCriteriaBuilder->addFilter(CompanyInterface::ID, $ids, 'in')->create();

        $result = [];
        foreach ($this->companyRepository->getList($criteria)->getItems() as $company) {
            $result[(int) $company->getId()] = (string) $company->getName();
        }

        return $result;
    }
}
