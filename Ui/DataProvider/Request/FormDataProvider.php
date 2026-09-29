<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Ui\DataProvider\Request;

use Aheadworks\Ca\Api\CompanyRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Config;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request\CollectionFactory;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface as HttpRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

class FormDataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param HttpRequest $httpRequest
     * @param RequestRepositoryInterface $requestRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param TimezoneInterface $timezone
     * @param Formatter $formatter
     * @param Config $config
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        private readonly HttpRequest $httpRequest,
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly TimezoneInterface $timezone,
        private readonly Formatter $formatter,
        private readonly Config $config,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }

    /**
     * Return form data of the requested entity.
     *
     * @return array
     */
    #[\Override]
    public function getData(): array
    {
        $requestId = (int) $this->httpRequest->getParam($this->getRequestFieldName());
        if ($requestId === 0) {
            return [];
        }

        try {
            $request = $this->requestRepository->getById($requestId);
        } catch (LocalizedException) {
            return [];
        }

        return [$requestId => $this->buildRequestData($request)];
    }

    /**
     * Build form data for a request.
     *
     * @param RequestInterface $request
     * @return array
     */
    private function buildRequestData(RequestInterface $request): array
    {
        return [
            'id' => $request->getId(),
            'status' => $request->getStatus(),
            'status_label' => $this->formatter->getStatusLabel($request->getStatus()),
            'company_name' => $this->getCompanyName($request->getCompanyId()),
            'customer' => $this->getCustomerLabel($request->getCustomerId()),
            'created_at' => $this->formatter->formatDate($request->getCreatedAt(), true),
            'customer_comment' => $request->getCustomerComment() ?? '—',
            'order_id' => $request->getOrderId() !== null ? '#' . $request->getOrderId() : '—',
            'expires_at' => $this->getExpirationValue($request),
            'admin_comment' => $request->getAdminComment(),
            'items' => array_map(
                fn (RequestItemInterface $item): array => [
                    'id' => $item->getId(),
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'qty' => $this->formatter->formatQty($item->getQty()),
                    'original_price' => $this->formatter->formatPrice($item->getOriginalPrice()),
                    'special_price' => $item->getSpecialPrice() ?? $item->getOriginalPrice(),
                ],
                $request->getItems()
            ),
        ];
    }

    /**
     * Return expiration date (store timezone, Y-m-d); pending requests get the default validity suggestion.
     *
     * @param RequestInterface $request
     * @return string
     */
    private function getExpirationValue(RequestInterface $request): string
    {
        $utc = $request->getExpiresAt();
        if ($utc !== null) {
            return $this->timezone->date(new \DateTime($utc, new \DateTimeZone('UTC')))->format('Y-m-d');
        }

        if ($request->getStatus() !== Status::Pending->value) {
            return '';
        }

        return $this->timezone->date()
            ->modify('+' . $this->config->getDefaultExpirationDays($request->getStoreId()) . ' days')
            ->format('Y-m-d');
    }

    /**
     * Return company name.
     *
     * @param int $companyId
     * @return string
     */
    private function getCompanyName(int $companyId): string
    {
        try {
            return (string) $this->companyRepository->get($companyId)->getName();
        } catch (LocalizedException) {
            return (string) __('Deleted company');
        }
    }

    /**
     * Return "Name <email>" label of the requester.
     *
     * @param int $customerId
     * @return string
     */
    private function getCustomerLabel(int $customerId): string
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (LocalizedException) {
            return (string) __('Deleted user');
        }

        return sprintf('%s %s <%s>', $customer->getFirstname(), $customer->getLastname(), $customer->getEmail());
    }
}
