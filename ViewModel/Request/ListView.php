<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\ViewModel\Request;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\BasketService;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class ListView implements ArgumentInterface
{
    private const int PAGE_SIZE = 100;

    /**
     * @var array<int, string>
     */
    private array $customerNames = [];

    /**
     * @param RequestRepositoryInterface $requestRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param CurrentCompanyUserProvider $companyUserProvider
     * @param AccessChecker $accessChecker
     * @param BasketService $basketService
     * @param CustomerRepositoryInterface $customerRepository
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly CurrentCompanyUserProvider $companyUserProvider,
        private readonly AccessChecker $accessChecker,
        private readonly BasketService $basketService,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * Return requests visible to the current user (whole company for company admins).
     *
     * @return RequestInterface[]
     */
    public function getRequests(): array
    {
        $companyId = $this->companyUserProvider->getCompanyId();
        if ($companyId === null) {
            return [];
        }

        $this->searchCriteriaBuilder->addFilter(RequestInterface::COMPANY_ID, $companyId);
        if (!$this->isCompanyAdmin()) {
            $this->searchCriteriaBuilder->addFilter(
                RequestInterface::CUSTOMER_ID,
                (int) $this->companyUserProvider->getCustomerId()
            );
        }
        $this->searchCriteriaBuilder->addSortOrder(
            $this->sortOrderBuilder->setField(RequestInterface::ID)->setDescendingDirection()->create()
        );
        $this->searchCriteriaBuilder->setPageSize(self::PAGE_SIZE);

        return $this->requestRepository->getList($this->searchCriteriaBuilder->create())->getItems();
    }

    /**
     * Check whether the requester column is shown.
     *
     * @return bool
     */
    public function isCompanyAdmin(): bool
    {
        return $this->accessChecker->isCompanyAdmin();
    }

    /**
     * Return requester full name.
     *
     * @param int $customerId
     * @return string
     */
    public function getCustomerName(int $customerId): string
    {
        if (!isset($this->customerNames[$customerId])) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $this->customerNames[$customerId] = trim($customer->getFirstname() . ' ' . $customer->getLastname());
            } catch (LocalizedException) {
                $this->customerNames[$customerId] = (string) __('Deleted user');
            }
        }

        return $this->customerNames[$customerId];
    }

    /**
     * Return request details URL.
     *
     * @param int $requestId
     * @return string
     */
    public function getViewUrl(int $requestId): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/request/view', ['id' => $requestId]);
    }

    /**
     * Return basket URL.
     *
     * @return string
     */
    public function getBasketUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/basket/index');
    }

    /**
     * Return number of items in the current user's basket.
     *
     * @return int
     */
    public function getBasketCount(): int
    {
        $customerId = $this->companyUserProvider->getCustomerId();

        return $customerId !== null ? count($this->basketService->getItems($customerId)) : 0;
    }
}
