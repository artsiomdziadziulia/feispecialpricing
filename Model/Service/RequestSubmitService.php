<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\BasketItemRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterfaceFactory;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterfaceFactory;
use Aheadworks\FeiSpecialPricing\Model\BuyRequestAwareInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class RequestSubmitService
{
    public const string EVENT_SUBMITTED = 'aw_fei_sp_request_submitted';

    /**
     * @param BasketItemRepositoryInterface $basketItemRepository
     * @param RequestRepositoryInterface $requestRepository
     * @param RequestInterfaceFactory $requestFactory
     * @param RequestItemInterfaceFactory $requestItemFactory
     * @param ProductRepositoryInterface $productRepository
     * @param StoreManagerInterface $storeManager
     * @param CurrentCompanyUserProvider $companyUserProvider
     * @param EventManager $eventManager
     * @param ProductConfigurationResolver $configurationResolver
     */
    public function __construct(
        private readonly BasketItemRepositoryInterface $basketItemRepository,
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly RequestInterfaceFactory $requestFactory,
        private readonly RequestItemInterfaceFactory $requestItemFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly CurrentCompanyUserProvider $companyUserProvider,
        private readonly EventManager $eventManager,
        private readonly ProductConfigurationResolver $configurationResolver
    ) {
    }

    /**
     * Convert the customer's basket into a pending special pricing request and clear the basket.
     *
     * @param CustomerInterface $customer
     * @param string|null $comment
     * @return RequestInterface
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function submit(CustomerInterface $customer, ?string $comment = null): RequestInterface
    {
        $customerId = (int) $customer->getId();
        $companyId = $this->companyUserProvider->getCompanyIdOf($customer);
        if ($companyId === null) {
            throw new LocalizedException(__('Only company users can submit special pricing requests.'));
        }

        $basketItems = $this->basketItemRepository->getByCustomerId($customerId);
        if ($basketItems === []) {
            throw new LocalizedException(__('Your special pricing basket is empty.'));
        }

        $storeId = (int) $this->storeManager->getStore()->getId();
        $items = [];
        foreach ($basketItems as $basketItem) {
            $item = $this->createRequestItem($basketItem, $storeId);
            if ($item !== null) {
                $items[] = $item;
            }
        }

        if ($items === []) {
            throw new LocalizedException(__('None of the products in your basket are available anymore.'));
        }

        $comment = $comment !== null ? trim($comment) : null;
        $request = $this->requestFactory->create();
        $request->setCompanyId($companyId)
            ->setCustomerId($customerId)
            ->setStoreId($storeId)
            ->setStatus(Status::Pending->value)
            ->setCustomerComment($comment !== '' ? $comment : null)
            ->setItems($items);

        $this->requestRepository->save($request);
        $this->basketItemRepository->deleteByCustomerId($customerId);

        $this->eventManager->dispatch(self::EVENT_SUBMITTED, ['request' => $request]);

        return $request;
    }

    /**
     * Build a request item snapshot from a basket item; null when the product no longer exists.
     *
     * @param BasketItemInterface $basketItem
     * @param int $storeId
     * @return RequestItemInterface|null
     */
    private function createRequestItem(BasketItemInterface $basketItem, int $storeId): ?RequestItemInterface
    {
        try {
            $product = $this->productRepository->getById($basketItem->getProductId(), false, $storeId);
        } catch (NoSuchEntityException) {
            return null;
        }

        $concrete = $this->configurationResolver->resolve($product, $basketItem->getBuyRequest());
        $price = $concrete instanceof Product
            ? $concrete->getPriceInfo()->getPrice('final_price')->getAmount()->getValue()
            : $concrete->getPrice();

        $item = $this->requestItemFactory->create();
        $item->setProductId((int) $product->getId())
            ->setSku((string) $concrete->getSku())
            ->setName((string) $concrete->getName())
            ->setQty($basketItem->getQty())
            ->setOriginalPrice((float) $price);
        if ($item instanceof BuyRequestAwareInterface) {
            $item->setBuyRequest($basketItem->getBuyRequest());
        }

        return $item;
    }
}
