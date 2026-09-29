<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Data\ItemPrice;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem\Collection as ItemCollection;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem\CollectionFactory as ItemCollectionFactory;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\ExpirationDate;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestDecisionService;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestManagement;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestManagementTest extends TestCase
{
    /**
     * @var RequestRepositoryInterface&MockObject
     */
    private MockObject $repositoryMock;

    /**
     * @var RequestDecisionService&MockObject
     */
    private MockObject $decisionServiceMock;

    /**
     * @var ItemCollectionFactory&MockObject
     */
    private MockObject $itemCollectionFactoryMock;

    /**
     * @var ExpirationDate&MockObject
     */
    private MockObject $expirationDateMock;

    /**
     * @var RequestManagement
     */
    private RequestManagement $management;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(RequestRepositoryInterface::class);
        $this->decisionServiceMock = $this->createMock(RequestDecisionService::class);
        $this->itemCollectionFactoryMock = $this->createMock(ItemCollectionFactory::class);
        $this->expirationDateMock = $this->createMock(ExpirationDate::class);

        $this->management = new RequestManagement(
            $this->repositoryMock,
            $this->decisionServiceMock,
            $this->itemCollectionFactoryMock,
            $this->expirationDateMock
        );
    }

    /**
     * Verify approve maps item prices, converts the expiration date and delegates to the decision service.
     *
     * @return void
     */
    public function testApproveDelegatesPricesAndExpiration(): void
    {
        $this->repositoryMock->method('getById')->with(7)->willReturn($this->createRequest(7, [11, 12]));
        $this->expirationDateMock->method('process')
            ->with([ExpirationDate::KEY => '2026-12-31'])
            ->willReturn([ExpirationDate::KEY => '2026-12-31 21:59:59']);

        $approved = $this->createMock(RequestInterface::class);
        $this->decisionServiceMock->expects($this->once())
            ->method('approve')
            ->with(7, [11 => 10.5, 12 => 20.0], '2026-12-31 21:59:59', 'OK')
            ->willReturn($approved);

        $result = $this->management->approve(
            7,
            [$this->createItemPrice(11, 10.5), $this->createItemPrice(12, 20.0)],
            '2026-12-31',
            'OK'
        );

        $this->assertSame($approved, $result);
    }

    /**
     * Verify approve rejects an item ID from another request.
     *
     * @return void
     */
    public function testApproveRejectsForeignItem(): void
    {
        $this->repositoryMock->method('getById')->willReturn($this->createRequest(7, [11]));
        $this->decisionServiceMock->expects($this->never())->method('approve');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Item #99 does not belong to request #7.');

        $this->management->approve(7, [$this->createItemPrice(99, 5.0)]);
    }

    /**
     * Verify reject delegates to the decision service.
     *
     * @return void
     */
    public function testRejectDelegates(): void
    {
        $rejected = $this->createMock(RequestInterface::class);
        $this->decisionServiceMock->expects($this->once())
            ->method('reject')
            ->with(7, 'No')
            ->willReturn($rejected);

        $this->assertSame($rejected, $this->management->reject(7, 'No'));
    }

    /**
     * Verify getList attaches items loaded in a single collection to each request.
     *
     * @return void
     */
    public function testGetListAttachesItems(): void
    {
        $first = $this->createMock(RequestInterface::class);
        $first->method('getId')->willReturn(1);
        $second = $this->createMock(RequestInterface::class);
        $second->method('getId')->willReturn(2);
        $searchResults = $this->createMock(RequestSearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([$first, $second]);
        $this->repositoryMock->method('getList')->willReturn($searchResults);

        $item = $this->createMock(RequestItemInterface::class);
        $item->method('getRequestId')->willReturn(1);
        $collection = $this->createMock(ItemCollection::class);
        $collection->method('addFieldToFilter')
            ->with(RequestItemInterface::REQUEST_ID, ['in' => [1, 2]])
            ->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([$item]));
        $this->itemCollectionFactoryMock->expects($this->once())->method('create')->willReturn($collection);

        $first->expects($this->once())->method('setItems')->with([$item]);
        $second->expects($this->once())->method('setItems')->with([]);

        $this->assertSame(
            $searchResults,
            $this->management->getList($this->createMock(SearchCriteriaInterface::class))
        );
    }

    /**
     * Create request mock with the given item IDs.
     *
     * @param int $requestId
     * @param int[] $itemIds
     * @return RequestInterface
     */
    private function createRequest(int $requestId, array $itemIds): RequestInterface
    {
        $items = [];
        foreach ($itemIds as $itemId) {
            $item = $this->createMock(RequestItemInterface::class);
            $item->method('getId')->willReturn($itemId);
            $items[] = $item;
        }

        $request = $this->createMock(RequestInterface::class);
        $request->method('getId')->willReturn($requestId);
        $request->method('getItems')->willReturn($items);

        return $request;
    }

    /**
     * Create item price DTO.
     *
     * @param int $itemId
     * @param float $price
     * @return ItemPrice
     */
    private function createItemPrice(int $itemId, float $price): ItemPrice
    {
        return (new ItemPrice())->setItemId($itemId)->setSpecialPrice($price);
    }
}
