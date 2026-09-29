<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestOrderLinker;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestToCartService;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\TestCase;

class RequestOrderLinkerTest extends TestCase
{
    /**
     * Approved request linked to order lines becomes "ordered" once.
     *
     * @return void
     */
    public function testMarkOrdered(): void
    {
        $linked = $this->createMock(OrderItem::class);
        $linked->method('getData')->with(RequestToCartService::ITEM_LINK_FIELD)->willReturn('11');
        $linkedSibling = $this->createMock(OrderItem::class);
        $linkedSibling->method('getData')->willReturn('12');
        $regular = $this->createMock(OrderItem::class);
        $regular->method('getData')->willReturn(null);

        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn([$linked, $linkedSibling, $regular]);
        $order->method('getEntityId')->willReturn(900);

        $requestItem = $this->createMock(RequestItemInterface::class);
        $requestItem->method('getRequestId')->willReturn(1);

        $request = $this->createMock(RequestInterface::class);
        $request->method('getStatus')->willReturn(Status::Approved->value);
        $request->expects($this->once())->method('setStatus')->with(Status::Ordered->value)->willReturnSelf();
        $request->expects($this->once())->method('setOrderId')->with(900)->willReturnSelf();

        $repository = $this->createMock(RequestRepositoryInterface::class);
        $repository->method('getItemById')->willReturn($requestItem);
        $repository->method('getById')->with(1)->willReturn($request);
        $repository->expects($this->once())->method('save')->with($request);

        $this->assertSame([1], (new RequestOrderLinker($repository))->markOrdered($order));
    }

    /**
     * Non-approved requests are left untouched.
     *
     * @return void
     */
    public function testSkipsNonApprovedRequest(): void
    {
        $linked = $this->createMock(OrderItem::class);
        $linked->method('getData')->willReturn('11');
        $order = $this->createMock(OrderInterface::class);
        $order->method('getItems')->willReturn([$linked]);

        $requestItem = $this->createMock(RequestItemInterface::class);
        $requestItem->method('getRequestId')->willReturn(1);
        $request = $this->createMock(RequestInterface::class);
        $request->method('getStatus')->willReturn(Status::Ordered->value);

        $repository = $this->createMock(RequestRepositoryInterface::class);
        $repository->method('getItemById')->willReturn($requestItem);
        $repository->method('getById')->willReturn($request);
        $repository->expects($this->never())->method('save');

        $this->assertSame([], (new RequestOrderLinker($repository))->markOrdered($order));
    }
}
