<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface RequestRepositoryInterface
{
    /**
     * Save request together with its items.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     * @throws CouldNotSaveException
     */
    public function save(RequestInterface $request): RequestInterface;

    /**
     * Load request with items by ID.
     *
     * @param int $id
     * @return RequestInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): RequestInterface;

    /**
     * Load request item by ID.
     *
     * @param int $itemId
     * @return RequestItemInterface
     * @throws NoSuchEntityException
     */
    public function getItemById(int $itemId): RequestItemInterface;

    /**
     * Return requests matching search criteria (items are not loaded).
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return RequestSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RequestSearchResultsInterface;
}
