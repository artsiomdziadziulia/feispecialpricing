<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service\Request;

use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\AdminComment;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\Composite;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\ExpirationDate;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor\ItemPrices;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\TestCase;

class PostDataProcessorTest extends TestCase
{
    /**
     * Dynamic rows become an ID => price map; empty price becomes null, comma decimal is normalized.
     *
     * @return void
     */
    public function testItemPrices(): void
    {
        $result = (new ItemPrices())->process([
            'items' => [
                ['id' => '11', 'special_price' => '12,50'],
                ['id' => '12', 'special_price' => ''],
                ['sku' => 'no-id'],
            ],
        ]);

        $this->assertSame([11 => '12.50', 12 => null], $result[ItemPrices::KEY]);
        $this->assertArrayNotHasKey('items', $result);
    }

    /**
     * Date is converted to UTC end of day; empty becomes null; invalid is rejected.
     *
     * @return void
     */
    public function testExpirationDate(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->expects($this->once())->method('convertConfigTimeToUtc')
            ->with('2026-10-15 23:59:59')->willReturn('2026-10-16 04:59:59');
        $processor = new ExpirationDate($timezone);

        $this->assertSame('2026-10-16 04:59:59', $processor->process(['expires_at' => '2026-10-15'])['expires_at']);
        $this->assertNull($processor->process(['expires_at' => ''])['expires_at']);

        $this->expectException(LocalizedException::class);
        $processor->process(['expires_at' => '2026-02-31']);
    }

    /**
     * Locale-formatted date from the admin date picker is normalized via the timezone service.
     *
     * @return void
     */
    public function testExpirationDateAcceptsLocaleFormat(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('date')->with('10/28/26', null, false, false)
            ->willReturn(new \DateTime('2026-10-28'));
        $timezone->expects($this->once())->method('convertConfigTimeToUtc')
            ->with('2026-10-28 23:59:59')->willReturn('2026-10-29 03:59:59');

        $result = (new ExpirationDate($timezone))->process(['expires_at' => '10/28/26']);

        $this->assertSame('2026-10-29 03:59:59', $result['expires_at']);
    }

    /**
     * Unparseable value is rejected with a user-facing error.
     *
     * @return void
     */
    public function testExpirationDateRejectsGarbage(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('date')->willThrowException(new \Exception('bad'));

        $this->expectException(LocalizedException::class);
        (new ExpirationDate($timezone))->process(['expires_at' => 'not a date']);
    }

    /**
     * Composite chains processors.
     *
     * @return void
     */
    public function testCompositeRunsAllProcessors(): void
    {
        $composite = new Composite([new ItemPrices(), new AdminComment()]);

        $result = $composite->process(['items' => [['id' => 1, 'special_price' => '5']], 'admin_comment' => '  ']);

        $this->assertSame([1 => '5'], $result[ItemPrices::KEY]);
        $this->assertNull($result[AdminComment::KEY]);
    }
}
