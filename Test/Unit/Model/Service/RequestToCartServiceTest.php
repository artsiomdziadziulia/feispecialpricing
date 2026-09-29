<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Model\BuyRequestAwareInterface;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestAvailabilityChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestToCartService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestToCartServiceTest extends TestCase
{
    /**
     * @var RequestAvailabilityChecker&MockObject
     */
    private MockObject $availabilityCheckerMock;

    /**
     * @var ProductRepositoryInterface&MockObject
     */
    private MockObject $productRepositoryMock;

    /**
     * @var CartRepositoryInterface&MockObject
     */
    private MockObject $cartRepositoryMock;

    /**
     * @var RequestToCartService
     */
    private RequestToCartService $service;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->availabilityCheckerMock = $this->createMock(RequestAvailabilityChecker::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);

        $dataObjectFactory = $this->createMock(DataObjectFactory::class);
        $dataObjectFactory->method('create')->willReturnCallback(
            static fn (array $args): DataObject => new DataObject($args['data'])
        );

        $this->service = new RequestToCartService(
            $this->availabilityCheckerMock,
            $this->productRepositoryMock,
            $this->cartRepositoryMock,
            $dataObjectFactory
        );
    }

    /**
     * Unavailable request cannot be added.
     *
     * @return void
     */
    public function testAddToCartFailsWhenNotPurchasable(): void
    {
        $this->availabilityCheckerMock->method('isPurchasable')->willReturn(false);
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->never())->method('addProduct');

        $this->expectException(LocalizedException::class);
        $this->service->addToCart($this->createMock(RequestInterface::class), $quote, 5);
    }

    /**
     * Approved item is added with locked custom price, previous linked line is replaced.
     *
     * @return void
     */
    public function testAddToCartLocksPrice(): void
    {
        $this->availabilityCheckerMock->method('isPurchasable')->willReturn(true);

        $requestItem = $this->createMockForIntersectionOfInterfaces(
            [RequestItemInterface::class, BuyRequestAwareInterface::class]
        );
        $requestItem->method('getId')->willReturn(11);
        $requestItem->method('getProductId')->willReturn(10);
        $requestItem->method('getSpecialPrice')->willReturn(7.5);
        $requestItem->method('getQty')->willReturn(3.0);
        $requestItem->method('getBuyRequest')->willReturn(['super_attribute' => [1 => 2]]);

        $request = $this->createMock(RequestInterface::class);
        $request->method('getItems')->willReturn([$requestItem]);
        $request->method('getStoreId')->willReturn(1);

        $product = $this->createMock(Product::class);
        $product->expects($this->once())->method('addCustomOption')
            ->with(RequestToCartService::ITEM_LINK_FIELD, '11');
        $this->productRepositoryMock->method('getById')->willReturn($product);

        $oldLine = $this->createMock(QuoteItem::class);
        $oldLine->method('getData')->with(RequestToCartService::ITEM_LINK_FIELD)->willReturn('11');
        $oldLine->method('getId')->willReturn(77);

        $newLine = $this->getMockBuilder(QuoteItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setCustomPrice', 'setData', 'getProduct'])
            ->addMethods(['setOriginalCustomPrice'])
            ->getMock();
        $newLine->expects($this->once())->method('setCustomPrice')->with(7.5);
        $newLine->expects($this->once())->method('setOriginalCustomPrice')->with(7.5);
        $newLine->expects($this->once())->method('setData')->with(RequestToCartService::ITEM_LINK_FIELD, 11);
        $newLine->method('getProduct')->willReturn($product);

        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$oldLine]);
        $quote->expects($this->once())->method('removeItem')->with(77);
        $quote->expects($this->once())->method('addProduct')
            ->with($product, $this->callback(
                static fn (DataObject $br): bool => $br->getData('qty') === 3.0
                    && $br->getData('super_attribute') === [1 => 2]
            ))
            ->willReturn($newLine);
        $this->cartRepositoryMock->expects($this->once())->method('save')->with($quote);

        $this->assertSame(1, $this->service->addToCart($request, $quote, 5));
    }

    /**
     * Quote error message is surfaced as exception.
     *
     * @return void
     */
    public function testAddToCartSurfacesQuoteError(): void
    {
        $this->availabilityCheckerMock->method('isPurchasable')->willReturn(true);

        $requestItem = $this->createMockForIntersectionOfInterfaces(
            [RequestItemInterface::class, BuyRequestAwareInterface::class]
        );
        $requestItem->method('getProductId')->willReturn(10);
        $requestItem->method('getSpecialPrice')->willReturn(7.5);
        $requestItem->method('getBuyRequest')->willReturn([]);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getItems')->willReturn([$requestItem]);

        $this->productRepositoryMock->method('getById')->willReturn($this->createMock(Product::class));
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([]);
        $quote->method('addProduct')->willReturn('Out of stock');
        $this->cartRepositoryMock->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);
        $this->service->addToCart($request, $quote, 5);
    }
}
