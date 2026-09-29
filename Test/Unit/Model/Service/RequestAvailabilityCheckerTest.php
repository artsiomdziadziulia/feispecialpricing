<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestAvailabilityChecker;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\TestCase;

class RequestAvailabilityCheckerTest extends TestCase
{
    private const int NOW = 1790000000;

    /**
     * @var RequestAvailabilityChecker
     */
    private RequestAvailabilityChecker $checker;

    /**
     * Set up test subject with a frozen clock.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtTimestamp')->willReturn(self::NOW);
        $this->checker = new RequestAvailabilityChecker($dateTime);
    }

    /**
     * Purchasability matrix.
     *
     * @dataProvider purchasableProvider
     * @param int $ownerId
     * @param string $status
     * @param string|null $expiresAt
     * @param bool $expected
     * @return void
     */
    public function testIsPurchasable(int $ownerId, string $status, ?string $expiresAt, bool $expected): void
    {
        $request = $this->createRequest($ownerId, 1, $status, $expiresAt);

        $this->assertSame($expected, $this->checker->isPurchasable($request, 5));
    }

    /**
     * Data for testIsPurchasable.
     *
     * @return array
     */
    public static function purchasableProvider(): array
    {
        $future = gmdate('Y-m-d H:i:s', self::NOW + 3600);
        $past = gmdate('Y-m-d H:i:s', self::NOW - 1);

        return [
            'approved, own, valid' => [5, Status::Approved->value, $future, true],
            'approved, no expiry' => [5, Status::Approved->value, null, true],
            'approved, expired' => [5, Status::Approved->value, $past, false],
            'approved, other user' => [6, Status::Approved->value, $future, false],
            'pending' => [5, Status::Pending->value, $future, false],
            'ordered' => [5, Status::Ordered->value, $future, false],
        ];
    }

    /**
     * View rights: same company and requester or company admin.
     *
     * @return void
     */
    public function testCanView(): void
    {
        $request = $this->createRequest(5, 1, Status::Pending->value, null);

        $this->assertTrue($this->checker->canView($request, 5, 1, false));
        $this->assertTrue($this->checker->canView($request, 7, 1, true));
        $this->assertFalse($this->checker->canView($request, 7, 1, false));
        $this->assertFalse($this->checker->canView($request, 5, 2, true));
        $this->assertFalse($this->checker->canView($request, 5, null, true));
    }

    /**
     * Build request stub.
     *
     * @param int $customerId
     * @param int $companyId
     * @param string $status
     * @param string|null $expiresAt
     * @return RequestInterface
     */
    private function createRequest(
        int $customerId,
        int $companyId,
        string $status,
        ?string $expiresAt
    ): RequestInterface {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getCustomerId')->willReturn($customerId);
        $request->method('getCompanyId')->willReturn($companyId);
        $request->method('getStatus')->willReturn($status);
        $request->method('getExpiresAt')->willReturn($expiresAt);

        return $request;
    }
}
