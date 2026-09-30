<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service\Request;

use Aheadworks\FeiSpecialPricing\Model\Service\Request\ExpirationDateResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ExpirationDateResolverTest extends TestCase
{
    /**
     * Date is converted to UTC end of that day in the store timezone.
     *
     * @return void
     */
    public function testResolveConvertsToUtcEndOfDay(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->expects($this->once())->method('convertConfigTimeToUtc')
            ->with('2026-10-15 23:59:59')->willReturn('2026-10-16 04:59:59');

        $this->assertSame('2026-10-16 04:59:59', (new ExpirationDateResolver($timezone))->resolve('2026-10-15'));
    }

    /**
     * Empty value means "use the configured validity period".
     *
     * @return void
     */
    public function testResolveEmptyReturnsNull(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->expects($this->never())->method('convertConfigTimeToUtc');
        $resolver = new ExpirationDateResolver($timezone);

        $this->assertNull($resolver->resolve(null));
        $this->assertNull($resolver->resolve('  '));
    }

    /**
     * Anything other than a real Y-m-d date is rejected.
     *
     * @param string $date
     * @return void
     */
    #[DataProvider('invalidDateProvider')]
    public function testResolveRejectsInvalidDate(string $date): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->expects($this->never())->method('convertConfigTimeToUtc');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Please enter a valid expiration date.');

        (new ExpirationDateResolver($timezone))->resolve($date);
    }

    /**
     * Invalid date values.
     *
     * @return array
     */
    public static function invalidDateProvider(): array
    {
        return [
            'non-existing day' => ['2026-02-31'],
            'locale format' => ['10/28/26'],
            'garbage' => ['not a date'],
        ];
    }
}
