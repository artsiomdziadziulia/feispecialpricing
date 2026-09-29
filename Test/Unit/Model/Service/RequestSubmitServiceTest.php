<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\BasketItemRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterfaceFactory;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Model\BuyRequestAwareInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterfaceFactory;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use Aheadworks\FeiSpecialPricing\Model\Service\ProductConfigurationResolver;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestSubmitService;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestSubmitServiceTest extends TestCase
{
    /**
     * @var BasketItemRepositoryInterface&MockObject
     */
    private MockObject $basketRepositoryMock;

    /**
     * @var RequestRepositoryInterface&MockObject
     */
    private MockObject $requestRepositoryMock;

    /**
     * @var RequestInterfaceFactory&MockObject
     */
    private MockObject $requestFactoryMock;

    /**
     * @var RequestItemInterfaceFactory&MockObject
     */
    private MockObject $itemFactoryMock;

    /**
     * @var ProductRepositoryInterface&MockObject
     */
    private MockObject $productRepositoryMock;

    /**
     * @var CurrentCompanyUserProvider&MockObject
     */
    private MockObject $companyUserProviderMock;

    /**
     * @var EventManager&MockObject
     */
    private MockObject $eventManagerMock;

    /**
     * @var CustomerInterface&MockObject
     */
    private MockObject $customerMock;

    /**
     * @var RequestSubmitService
     */
    private RequestSubmitService $service;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->basketRepositoryMock = $this->createMock(BasketItemRepositoryInterface::class);
        $this->requestRepositoryMock = $this->createMock(RequestRepositoryInterface::class);
        $this->requestFactoryMock = $this->createMock(RequestInterfaceFactory::class);
        $this->itemFactoryMock = $this->createMock(RequestItemInterfaceFactory::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->companyUserProviderMock = $this->createMock(CurrentCompanyUserProvider::class);
        $this->eventManagerMock = $this->createMock(EventManager::class);

        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->customerMock = $this->createMock(CustomerInterface::class);
        $this->customerMock->method('getId')->willReturn(5);

        $resolver = $this->createMock(ProductConfigurationResolver::class);
        $resolver->method('resolve')->willReturnArgument(0);

        $this->service = new RequestSubmitService(
            $this->basketRepositoryMock,
            $this->requestRepositoryMock,
            $this->requestFactoryMock,
            $this->itemFactoryMock,
            $this->productRepositoryMock,
            $storeManager,
            $this->companyUserProviderMock,
            $this->eventManagerMock,
            $resolver
        );
    }

    /**
     * Non-company customer cannot submit.
     *
     * @return void
     */
    public function testSubmitRequiresCompany(): void
    {
        $this->companyUserProviderMock->method('getCompanyIdOf')->willReturn(null);

        $this->expectException(LocalizedException::class);
        $this->service->submit($this->customerMock);
    }

    /**
     * Empty basket cannot be submitted.
     *
     * @return void
     */
    public function testSubmitRequiresItems(): void
    {
        $this->companyUserProviderMock->method('getCompanyIdOf')->willReturn(3);
        $this->basketRepositoryMock->method('getByCustomerId')->willReturn([]);

        $this->expectException(LocalizedException::class);
        $this->service->submit($this->customerMock);
    }

    /**
     * Basket is converted to a pending request, cleared, and event dispatched; deleted products are skipped.
     *
     * @return void
     */
    public function testSubmitCreatesPendingRequest(): void
    {
        $this->companyUserProviderMock->method('getCompanyIdOf')->willReturn(3);

        $basketItem = $this->createMock(BasketItemInterface::class);
        $basketItem->method('getProductId')->willReturn(10);
        $basketItem->method('getQty')->willReturn(4.0);
        $basketItem->method('getBuyRequest')->willReturn(['super_attribute' => [1 => 2]]);
        $missingItem = $this->createMock(BasketItemInterface::class);
        $missingItem->method('getProductId')->willReturn(404);
        $this->basketRepositoryMock->method('getByCustomerId')->willReturn([$basketItem, $missingItem]);

        $product = $this->createMock(ProductInterface::class);
        $product->method('getId')->willReturn(10);
        $product->method('getSku')->willReturn('SKU-10');
        $product->method('getName')->willReturn('Vest');
        $product->method('getPrice')->willReturn(100.0);
        $this->productRepositoryMock->method('getById')->willReturnCallback(
            static function (int $id) use ($product) {
                if ($id === 404) {
                    throw new NoSuchEntityException();
                }
                return $product;
            }
        );

        $requestItem = $this->createMockForIntersectionOfInterfaces(
            [RequestItemInterface::class, BuyRequestAwareInterface::class]
        );
        foreach (['setProductId', 'setSku', 'setName', 'setQty', 'setOriginalPrice', 'setBuyRequest'] as $setter) {
            $requestItem->method($setter)->willReturnSelf();
        }
        $requestItem->expects($this->once())->method('setQty')->with(4.0)->willReturnSelf();
        $requestItem->expects($this->once())->method('setOriginalPrice')->with(100.0)->willReturnSelf();
        $this->itemFactoryMock->expects($this->once())->method('create')->willReturn($requestItem);

        $request = $this->createMock(RequestInterface::class);
        $request->method('setCompanyId')->with(3)->willReturnSelf();
        $request->method('setCustomerId')->with(5)->willReturnSelf();
        $request->method('setStoreId')->with(1)->willReturnSelf();
        $request->expects($this->once())->method('setStatus')->with(Status::Pending->value)->willReturnSelf();
        $request->expects($this->once())->method('setCustomerComment')->with('Please')->willReturnSelf();
        $request->expects($this->once())->method('setItems')->with([$requestItem])->willReturnSelf();
        $this->requestFactoryMock->method('create')->willReturn($request);

        $this->requestRepositoryMock->expects($this->once())->method('save')->with($request);
        $this->basketRepositoryMock->expects($this->once())->method('deleteByCustomerId')->with(5);
        $this->eventManagerMock->expects($this->once())->method('dispatch')
            ->with(RequestSubmitService::EVENT_SUBMITTED, ['request' => $request]);

        $this->assertSame($request, $this->service->submit($this->customerMock, ' Please '));
    }
}
