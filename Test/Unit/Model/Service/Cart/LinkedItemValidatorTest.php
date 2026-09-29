<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service\Cart;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Service\Cart\LinkedItemValidator;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestAvailabilityChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestToCartService;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LinkedItemValidatorTest extends TestCase
{
    /**
     * @var RequestRepositoryInterface&MockObject
     */
    private MockObject $repositoryMock;

    /**
     * @var RequestAvailabilityChecker&MockObject
     */
    private MockObject $availabilityCheckerMock;

    /**
     * @var LinkedItemValidator
     */
    private LinkedItemValidator $validator;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(RequestRepositoryInterface::class);
        $this->availabilityCheckerMock = $this->createMock(RequestAvailabilityChecker::class);
        $this->validator = new LinkedItemValidator($this->repositoryMock, $this->availabilityCheckerMock);
    }

    /**
     * Regular line is never validated.
     *
     * @return void
     */
    public function testUnlinkedItemHasNoViolation(): void
    {
        $item = $this->createQuoteItem(0, 100.0);
        $this->repositoryMock->expects($this->never())->method('getItemById');

        $this->assertNull($this->validator->getViolation($item));
    }

    /**
     * Qty above approved qty is reported.
     *
     * @return void
     */
    public function testQtyViolation(): void
    {
        $this->mockRequestItem(3.0);

        $this->assertNotNull($this->validator->getQtyViolation($this->createQuoteItem(11, 4.0)));
        $this->assertNull($this->validator->getQtyViolation($this->createQuoteItem(11, 3.0)));
    }

    /**
     * Not purchasable request is reported.
     *
     * @return void
     */
    public function testAvailabilityViolation(): void
    {
        $this->mockRequestItem(3.0);
        $this->repositoryMock->method('getById')->willReturn($this->createMock(RequestInterface::class));
        $this->availabilityCheckerMock->method('isPurchasable')->with($this->anything(), 5)->willReturn(false);

        $this->assertNotNull($this->validator->getAvailabilityViolation($this->createQuoteItem(11, 1.0)));
    }

    /**
     * Deleted request item is reported as unavailable.
     *
     * @return void
     */
    public function testMissingRequestItemIsUnavailable(): void
    {
        $this->repositoryMock->method('getItemById')->willThrowException(new NoSuchEntityException());

        $this->assertNotNull($this->validator->getAvailabilityViolation($this->createQuoteItem(11, 1.0)));
    }

    /**
     * Valid line passes.
     *
     * @return void
     */
    public function testValidLine(): void
    {
        $this->mockRequestItem(3.0);
        $this->repositoryMock->method('getById')->willReturn($this->createMock(RequestInterface::class));
        $this->availabilityCheckerMock->method('isPurchasable')->willReturn(true);

        $this->assertNull($this->validator->getViolation($this->createQuoteItem(11, 2.0)));
    }

    /**
     * Configure repository to return a request item with the approved qty.
     *
     * @param float $qty
     * @return void
     */
    private function mockRequestItem(float $qty): void
    {
        $requestItem = $this->createMock(RequestItemInterface::class);
        $requestItem->method('getQty')->willReturn($qty);
        $requestItem->method('getRequestId')->willReturn(1);
        $this->repositoryMock->method('getItemById')->with(11)->willReturn($requestItem);
    }

    /**
     * Build quote item stub.
     *
     * @param int $linkId
     * @param float $qty
     * @return QuoteItem
     */
    private function createQuoteItem(int $linkId, float $qty): QuoteItem
    {
        $quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->getMock();
        $quote->method('getCustomerId')->willReturn(5);

        $item = $this->createMock(QuoteItem::class);
        $item->method('getData')->with(RequestToCartService::ITEM_LINK_FIELD)->willReturn($linkId ?: null);
        $item->method('getQty')->willReturn($qty);
        $item->method('getName')->willReturn('Vest');
        $item->method('getQuote')->willReturn($quote);

        return $item;
    }
}
