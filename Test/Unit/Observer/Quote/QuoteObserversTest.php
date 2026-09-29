<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Observer\Quote;

use Aheadworks\FeiSpecialPricing\Model\Service\Cart\LinkedItemValidator;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestToCartService;
use Aheadworks\FeiSpecialPricing\Observer\Quote\ReleaseUnavailablePricesObserver;
use Aheadworks\FeiSpecialPricing\Observer\Quote\ValidateBeforeSubmitObserver;
use Aheadworks\FeiSpecialPricing\Observer\Quote\ValidateLinkedItemQtyObserver;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteObserversTest extends TestCase
{
    /**
     * @var LinkedItemValidator&MockObject
     */
    private MockObject $validatorMock;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validatorMock = $this->createMock(LinkedItemValidator::class);
    }

    /**
     * Qty violation aborts the cart update.
     *
     * @return void
     */
    public function testQtyObserverThrowsOnViolation(): void
    {
        $this->validatorMock->method('getQtyViolation')->willReturn(__('Too many'));

        $this->expectException(LocalizedException::class);
        (new ValidateLinkedItemQtyObserver($this->validatorMock))
            ->execute($this->createObserver(['item' => $this->createMock(QuoteItem::class)]));
    }

    /**
     * Submit observer aborts order placement on invalid line.
     *
     * @return void
     */
    public function testSubmitObserverThrowsOnViolation(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->method('getAllVisibleItems')->willReturn([$this->createMock(QuoteItem::class)]);
        $this->validatorMock->method('getViolation')->willReturn(__('Expired'));

        $this->expectException(LocalizedException::class);
        (new ValidateBeforeSubmitObserver($this->validatorMock))->execute($this->createObserver(['quote' => $quote]));
    }

    /**
     * Unavailable line loses custom price and link; valid line is untouched.
     *
     * @return void
     */
    public function testReleaseObserverResetsUnavailableLine(): void
    {
        $stale = $this->createMock(QuoteItem::class);
        $cleared = [];
        $stale->expects($this->exactly(3))->method('setData')
            ->willReturnCallback(function (string $key, $value) use (&$cleared, $stale) {
                $cleared[$key] = $value;

                return $stale;
            });

        $valid = $this->createMock(QuoteItem::class);
        $valid->expects($this->never())->method('setData');

        $quote = $this->createMock(Quote::class);
        $quote->method('getIsActive')->willReturn(true);
        $quote->method('getAllVisibleItems')->willReturn([$stale, $valid]);

        $this->validatorMock->method('getLinkId')->willReturn(11);
        $this->validatorMock->method('getAvailabilityViolation')->willReturnCallback(
            static fn ($item) => $item === $stale ? __('Expired') : null
        );

        (new ReleaseUnavailablePricesObserver($this->validatorMock))
            ->execute($this->createObserver(['quote' => $quote]));

        $this->assertSame(
            ['custom_price' => null, 'original_custom_price' => null, RequestToCartService::ITEM_LINK_FIELD => null],
            $cleared
        );
    }

    /**
     * Build observer with event data.
     *
     * @param array $data
     * @return Observer
     */
    private function createObserver(array $data): Observer
    {
        return new Observer(['event' => new Event($data)]);
    }
}
