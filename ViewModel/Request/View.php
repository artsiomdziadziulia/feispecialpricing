<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\ViewModel\Request;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestAvailabilityChecker;
use Magento\Framework\App\RequestInterface as HttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class View implements ArgumentInterface
{
    /**
     * @var RequestInterface|null
     */
    private ?RequestInterface $request = null;

    /**
     * @var bool
     */
    private bool $isResolved = false;

    /**
     * @param HttpRequest $httpRequest
     * @param RequestRepositoryInterface $requestRepository
     * @param CurrentCompanyUserProvider $companyUserProvider
     * @param AccessChecker $accessChecker
     * @param RequestAvailabilityChecker $availabilityChecker
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly HttpRequest $httpRequest,
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly CurrentCompanyUserProvider $companyUserProvider,
        private readonly AccessChecker $accessChecker,
        private readonly RequestAvailabilityChecker $availabilityChecker,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * Return the request from the "id" param when the current user may view it.
     *
     * @return RequestInterface|null
     */
    public function getRequest(): ?RequestInterface
    {
        if ($this->isResolved) {
            return $this->request;
        }

        $this->isResolved = true;
        $customerId = $this->companyUserProvider->getCustomerId();
        if ($customerId === null) {
            return null;
        }

        try {
            $request = $this->requestRepository->getById((int) $this->httpRequest->getParam('id'));
        } catch (NoSuchEntityException) {
            return null;
        }

        if ($this->availabilityChecker->canView(
            $request,
            $customerId,
            $this->companyUserProvider->getCompanyId(),
            $this->accessChecker->isCompanyAdmin()
        )) {
            $this->request = $request;
        }

        return $this->request;
    }

    /**
     * Check whether the "Add to Cart" button is shown.
     *
     * @return bool
     */
    public function canAddToCart(): bool
    {
        $request = $this->getRequest();
        $customerId = $this->companyUserProvider->getCustomerId();

        return $request !== null
            && $customerId !== null
            && $this->availabilityChecker->isPurchasable($request, $customerId);
    }

    /**
     * Return "Add to Cart" form action URL.
     *
     * @return string
     */
    public function getAddToCartUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/request/addToCart');
    }

    /**
     * Return requests list URL.
     *
     * @return string
     */
    public function getListUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/request/index');
    }
}
