<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\FeiBase\Model\Service\Cart\ProductAddRestrictionChecker;
use Aheadworks\FeiSpecialPricing\Api\BasketItemRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterfaceFactory;
use Aheadworks\FeiSpecialPricing\Model\Service\Basket\BuyRequestNormalizer;
use Aheadworks\FeiSpecialPricing\Model\Service\BasketService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasketServiceTest extends TestCase
{
    /**
     * @var BasketItemRepositoryInterface&MockObject
     */
    private MockObject $repositoryMock;

    /**
     * @var BasketItemInterfaceFactory&MockObject
     */
    private MockObject $factoryMock;

    /**
     * @var ProductRepositoryInterface&MockObject
     */
    private MockObject $productRepositoryMock;

    /**
     * @var ProductAddRestrictionChecker&MockObject
     */
    private MockObject $restrictionCheckerMock;

    /**
     * @var BasketService
     */
    private BasketService $service;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(BasketItemRepositoryInterface::class);
        $this->factoryMock = $this->createMock(BasketItemInterfaceFactory::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->restrictionCheckerMock = $this->createMock(ProductAddRestrictionChecker::class);

        $this->service = new BasketService(
            $this->repositoryMock,
            $this->factoryMock,
            $this->productRepositoryMock,
            $this->restrictionCheckerMock,
            new BuyRequestNormalizer()
        );
    }

    /**
     * New product creates a new basket row with normalized options.
     *
     * @return void
     */
    public function testAddCreatesNewItem(): void
    {
        $this->mockProduct(ProductStatus::STATUS_ENABLED);
        $this->repositoryMock->method('getByCustomerId')->willReturn([]);

        $item = $this->createMock(BasketItemInterface::class);
        $item->expects($this->once())->method('setCustomerId')->with(5)->willReturnSelf();
        $item->expects($this->once())->method('setProductId')->with(10)->willReturnSelf();
        $item->expects($this->once())->method('setQty')->with(2.0)->willReturnSelf();
        $item->expects($this->once())->method('setBuyRequest')
            ->with(['super_attribute' => [93 => 1]])->willReturnSelf();
        $this->factoryMock->method('create')->willReturn($item);
        $this->repositoryMock->expects($this->once())->method('save')->with($item)->willReturn($item);

        $this->service->add(5, 10, 2.0, ['form_key' => 'x', 'super_attribute' => [93 => 1]]);
    }

    /**
     * Same product and options increase qty of the existing row.
     *
     * @return void
     */
    public function testAddMergesIdenticalItem(): void
    {
        $this->mockProduct(ProductStatus::STATUS_ENABLED);

        $existing = $this->createMock(BasketItemInterface::class);
        $existing->method('getProductId')->willReturn(10);
        $existing->method('getBuyRequest')->willReturn([]);
        $existing->method('getQty')->willReturn(3.0);
        $existing->expects($this->once())->method('setQty')->with(5.0)->willReturnSelf();
        $this->repositoryMock->method('getByCustomerId')->willReturn([$existing]);
        $this->repositoryMock->expects($this->once())->method('save')->with($existing)->willReturn($existing);
        $this->factoryMock->expects($this->never())->method('create');

        $this->service->add(5, 10, 2.0);
    }

    /**
     * Zero qty is rejected.
     *
     * @return void
     */
    public function testAddRejectsZeroQty(): void
    {
        $this->expectException(LocalizedException::class);
        $this->service->add(5, 10, 0.0);
    }

    /**
     * Disabled product is rejected.
     *
     * @return void
     */
    public function testAddRejectsDisabledProduct(): void
    {
        $this->mockProduct(ProductStatus::STATUS_DISABLED);
        $this->expectException(LocalizedException::class);
        $this->service->add(5, 10, 1.0);
    }

    /**
     * FEI group lock is enforced.
     *
     * @return void
     */
    public function testAddRejectsLockedProduct(): void
    {
        $this->mockProduct(ProductStatus::STATUS_ENABLED);
        $this->restrictionCheckerMock->method('assertCanAddToCart')
            ->willThrowException(new LocalizedException(__('Locked')));
        $this->repositoryMock->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);
        $this->service->add(5, 10, 1.0);
    }

    /**
     * Foreign basket item cannot be removed.
     *
     * @return void
     */
    public function testRemoveRejectsForeignItem(): void
    {
        $item = $this->createMock(BasketItemInterface::class);
        $item->method('getCustomerId')->willReturn(99);
        $this->repositoryMock->method('getById')->willReturn($item);
        $this->repositoryMock->expects($this->never())->method('delete');

        $this->expectException(NoSuchEntityException::class);
        $this->service->remove(5, 1);
    }

    /**
     * Qty <= 0 deletes the item.
     *
     * @return void
     */
    public function testUpdateQtyZeroDeletesItem(): void
    {
        $item = $this->createMock(BasketItemInterface::class);
        $item->method('getCustomerId')->willReturn(5);
        $this->repositoryMock->method('getById')->willReturn($item);
        $this->repositoryMock->expects($this->once())->method('delete')->with($item);
        $this->repositoryMock->expects($this->never())->method('save');

        $this->service->updateQty(5, 1, 0.0);
    }

    /**
     * Configure product repository to return a product with given status.
     *
     * @param int $status
     * @return void
     */
    private function mockProduct(int $status): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getStatus')->willReturn($status);
        $this->productRepositoryMock->method('getById')->willReturn($product);
    }
}
