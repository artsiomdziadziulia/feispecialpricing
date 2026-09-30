<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\ItemPriceInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestManagementInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem\CollectionFactory as ItemCollectionFactory;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\ExpirationDateResolver;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;

class RequestManagement implements RequestManagementInterface
{
    /**
     * @param RequestRepositoryInterface $requestRepository
     * @param RequestDecisionService $decisionService
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param ExpirationDateResolver $expirationDateResolver
     */
    public function __construct(
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly RequestDecisionService $decisionService,
        private readonly ItemCollectionFactory $itemCollectionFactory,
        private readonly ExpirationDateResolver $expirationDateResolver
    ) {
    }

    /**
     * Return special pricing requests with their items loaded in one query.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return RequestSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RequestSearchResultsInterface
    {
        $searchResults = $this->requestRepository->getList($searchCriteria);
        $requests = $searchResults->getItems();
        if ($requests === []) {
            return $searchResults;
        }

        $requestIds = array_map(static fn (RequestInterface $request): int => (int) $request->getId(), $requests);
        $itemCollection = $this->itemCollectionFactory->create()
            ->addFieldToFilter(RequestItemInterface::REQUEST_ID, ['in' => $requestIds])
            ->setOrder(RequestItemInterface::ID, 'ASC');

        $itemsByRequest = [];
        /** @var RequestItemInterface $item */
        foreach ($itemCollection as $item) {
            $itemsByRequest[$item->getRequestId()][] = $item;
        }

        foreach ($requests as $request) {
            $request->setItems($itemsByRequest[(int) $request->getId()] ?? []);
        }

        return $searchResults;
    }

    /**
     * Return a special pricing request with its items.
     *
     * @param int $requestId
     * @return RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $requestId): RequestInterface
    {
        return $this->requestRepository->getById($requestId);
    }

    /**
     * Approve a request, setting the special price of every item.
     *
     * @param int $requestId
     * @param ItemPriceInterface[] $items
     * @param string|null $expiresAt
     * @param string|null $adminComment
     * @return RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function approve(
        int $requestId,
        array $items,
        ?string $expiresAt = null,
        ?string $adminComment = null
    ): RequestInterface {
        $request = $this->requestRepository->getById($requestId);
        $requestItemIds = array_map(
            static fn (RequestItemInterface $item): int => (int) $item->getId(),
            $request->getItems()
        );

        $specialPrices = [];
        foreach ($items as $itemPrice) {
            $itemId = $itemPrice->getItemId();
            if (!in_array($itemId, $requestItemIds, true)) {
                throw new LocalizedException(
                    __('Item #%1 does not belong to request #%2.', $itemId, $requestId)
                );
            }
            $specialPrices[$itemId] = $itemPrice->getSpecialPrice();
        }

        return $this->decisionService->approve(
            $requestId,
            $specialPrices,
            $this->expirationDateResolver->resolve($expiresAt),
            $adminComment
        );
    }

    /**
     * Reject a request.
     *
     * @param int $requestId
     * @param string|null $adminComment
     * @return RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function reject(int $requestId, ?string $adminComment = null): RequestInterface
    {
        return $this->decisionService->reject($requestId, $adminComment);
    }
}
