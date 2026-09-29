<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterfaceFactory;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request as RequestResource;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request\CollectionFactory as RequestCollectionFactory;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem as RequestItemResource;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem\CollectionFactory as ItemCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class RequestRepository implements RequestRepositoryInterface
{
    /**
     * @var array<int, RequestInterface>
     */
    private array $registry = [];

    /**
     * @param RequestResource $resource
     * @param RequestFactory $factory
     * @param RequestItemResource $itemResource
     * @param RequestItemFactory $itemFactory
     * @param RequestCollectionFactory $collectionFactory
     * @param ItemCollectionFactory $itemCollectionFactory
     * @param RequestSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private readonly RequestResource $resource,
        private readonly RequestFactory $factory,
        private readonly RequestItemResource $itemResource,
        private readonly RequestItemFactory $itemFactory,
        private readonly RequestCollectionFactory $collectionFactory,
        private readonly ItemCollectionFactory $itemCollectionFactory,
        private readonly RequestSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * Save request together with its items in a single transaction.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     * @throws CouldNotSaveException
     */
    public function save(RequestInterface $request): RequestInterface
    {
        $connection = $this->resource->getConnection();
        $connection->beginTransaction();
        try {
            /** @var Request $request */
            $this->resource->save($request);
            foreach ($request->getItems() as $item) {
                /** @var RequestItem $item */
                $item->setRequestId((int) $request->getId());
                $this->itemResource->save($item);
            }
            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
            throw new CouldNotSaveException(__('Unable to save the special pricing request.'), $exception);
        }

        $this->registry[(int) $request->getId()] = $request;

        return $request;
    }

    /**
     * Load request with items by ID.
     *
     * @param int $id
     * @return RequestInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): RequestInterface
    {
        if (isset($this->registry[$id])) {
            return $this->registry[$id];
        }

        $request = $this->factory->create();
        $this->resource->load($request, $id);
        if (!$request->getId()) {
            throw NoSuchEntityException::singleField(RequestInterface::ID, $id);
        }

        /** @var RequestItemInterface[] $items */
        $items = $this->itemCollectionFactory->create()
            ->addFieldToFilter(RequestItemInterface::REQUEST_ID, ['eq' => $id])
            ->setOrder(RequestItemInterface::ID, 'ASC')
            ->getItems();
        $request->setItems(array_values($items));

        return $this->registry[$id] = $request;
    }

    /**
     * Load request item by ID.
     *
     * @param int $itemId
     * @return RequestItemInterface
     * @throws NoSuchEntityException
     */
    public function getItemById(int $itemId): RequestItemInterface
    {
        $item = $this->itemFactory->create();
        $this->itemResource->load($item, $itemId);
        if (!$item->getId()) {
            throw NoSuchEntityException::singleField(RequestItemInterface::ID, $itemId);
        }

        return $item;
    }

    /**
     * Return requests matching search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return RequestSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): RequestSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        /** @var RequestInterface[] $items */
        $items = array_values($collection->getItems());
        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }
}
