<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface RequestManagementInterface
{
    /**
     * Return special pricing requests with their items.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RequestSearchResultsInterface;

    /**
     * Return a special pricing request with its items.
     *
     * @param int $requestId
     * @return \Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $requestId): RequestInterface;

    /**
     * Approve a request, setting the special price of every item.
     *
     * @param int $requestId
     * @param \Aheadworks\FeiSpecialPricing\Api\Data\ItemPriceInterface[] $items
     * @param string|null $expiresAt Last valid day (Y-m-d, store timezone); default validity period when empty
     * @param string|null $adminComment
     * @return \Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function approve(
        int $requestId,
        array $items,
        ?string $expiresAt = null,
        ?string $adminComment = null
    ): RequestInterface;

    /**
     * Reject a request.
     *
     * @param int $requestId
     * @param string|null $adminComment
     * @return \Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function reject(int $requestId, ?string $adminComment = null): RequestInterface;
}
