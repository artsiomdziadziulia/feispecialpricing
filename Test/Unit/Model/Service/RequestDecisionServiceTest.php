<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Config;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestDecisionService;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestDecisionServiceTest extends TestCase
{
    private const int NOW = 1790000000;

    /**
     * @var RequestRepositoryInterface&MockObject
     */
    private MockObject $repositoryMock;

    /**
     * @var EventManager&MockObject
     */
    private MockObject $eventManagerMock;

    /**
     * @var RequestDecisionService
     */
    private RequestDecisionService $service;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(RequestRepositoryInterface::class);
        $this->eventManagerMock = $this->createMock(EventManager::class);

        $config = $this->createMock(Config::class);
        $config->method('getDefaultExpirationDays')->willReturn(10);

        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW);
        $dateTime->method('gmtDate')->willReturnCallback(
            static fn (string $format, int $ts): string => gmdate($format, $ts)
        );

        $this->service = new RequestDecisionService(
            $this->repositoryMock,
            $config,
            $dateTime,
            $this->eventManagerMock
        );
    }

    /**
     * Approve sets prices, default expiration, status and dispatches event.
     *
     * @return void
     */
    public function testApproveSetsPricesAndDefaultExpiration(): void
    {
        $item = $this->createMock(RequestItemInterface::class);
        $item->method('getId')->willReturn(11);
        $item->expects($this->once())->method('setSpecialPrice')->with(12.5)->willReturnSelf();

        $request = $this->createRequest(Status::Pending, [$item]);
        $request->expects($this->once())->method('setStatus')->with(Status::Approved->value)->willReturnSelf();
        $request->expects($this->once())->method('setExpiresAt')
            ->with(gmdate('Y-m-d H:i:s', self::NOW + 10 * 86400))->willReturnSelf();
        $request->expects($this->once())->method('setAdminComment')->with('OK')->willReturnSelf();

        $this->repositoryMock->expects($this->once())->method('save')->with($request);
        $this->eventManagerMock->expects($this->once())->method('dispatch')
            ->with(RequestDecisionService::EVENT_DECIDED, ['request' => $request]);

        $this->service->approve(1, [11 => '12.5'], null, '  OK ');
    }

    /**
     * Missing special price blocks approval.
     *
     * @return void
     */
    public function testApproveRequiresPriceForEveryItem(): void
    {
        $item = $this->createMock(RequestItemInterface::class);
        $item->method('getId')->willReturn(11);
        $this->createRequest(Status::Pending, [$item]);
        $this->repositoryMock->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);
        $this->service->approve(1, [11 => '']);
    }

    /**
     * Past expiration date is rejected.
     *
     * @return void
     */
    public function testApproveRejectsPastExpiration(): void
    {
        $item = $this->createMock(RequestItemInterface::class);
        $item->method('getId')->willReturn(11);
        $item->method('setSpecialPrice')->willReturnSelf();
        $this->createRequest(Status::Pending, [$item]);

        $this->expectException(LocalizedException::class);
        $this->service->approve(1, [11 => 5], gmdate('Y-m-d H:i:s', self::NOW - 60));
    }

    /**
     * Ordered request cannot be decided again.
     *
     * @return void
     */
    public function testRejectFailsForOrderedRequest(): void
    {
        $this->createRequest(Status::Ordered, []);
        $this->repositoryMock->expects($this->never())->method('save');

        $this->expectException(LocalizedException::class);
        $this->service->reject(1);
    }

    /**
     * Reject sets status and dispatches event.
     *
     * @return void
     */
    public function testRejectPendingRequest(): void
    {
        $request = $this->createRequest(Status::Pending, []);
        $request->expects($this->once())->method('setStatus')->with(Status::Rejected->value)->willReturnSelf();
        $request->method('setAdminComment')->willReturnSelf();
        $this->repositoryMock->expects($this->once())->method('save');
        $this->eventManagerMock->expects($this->once())->method('dispatch');

        $this->service->reject(1, 'No');
    }

    /**
     * Build request stub returned by the repository.
     *
     * @param Status $status
     * @param RequestItemInterface[] $items
     * @return RequestInterface&MockObject
     */
    private function createRequest(Status $status, array $items): MockObject
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getId')->willReturn(1);
        $request->method('getStoreId')->willReturn(1);
        $request->method('getStatus')->willReturn($status->value);
        $request->method('getItems')->willReturn($items);
        $this->repositoryMock->method('getById')->with(1)->willReturn($request);

        return $request;
    }
}
