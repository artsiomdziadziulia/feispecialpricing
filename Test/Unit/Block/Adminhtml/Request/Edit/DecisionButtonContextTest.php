<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Block\Adminhtml\Request\Edit;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Block\Adminhtml\Request\Edit\DecisionButtonContext;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Framework\App\RequestInterface as HttpRequest;
use PHPUnit\Framework\TestCase;

class DecisionButtonContextTest extends TestCase
{
    /**
     * Buttons are available only for pending / approved requests.
     *
     * @dataProvider statusProvider
     * @param Status $status
     * @param bool $expected
     * @return void
     */
    public function testButtonVisibility(Status $status, bool $expected): void
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $httpRequest->method('getParam')->willReturn('1');
        $request = $this->createMock(RequestInterface::class);
        $request->method('getStatus')->willReturn($status->value);
        $repository = $this->createMock(RequestRepositoryInterface::class);
        $repository->method('getById')->willReturn($request);

        $context = new DecisionButtonContext($httpRequest, $repository);
        $button = $context->buildButton('Approve', 'approve', 'primary', 30);

        $this->assertSame($expected, $button !== []);
        if ($expected) {
            $action = $button['data_attribute']['mage-init']['buttonAdapter']['actions'][0];
            $this->assertSame([true, ['decision' => 'approve']], $action['params']);
        }
    }

    /**
     * Data for testButtonVisibility.
     *
     * @return array
     */
    public static function statusProvider(): array
    {
        return [
            'pending' => [Status::Pending, true],
            'approved' => [Status::Approved, true],
            'rejected' => [Status::Rejected, false],
            'ordered' => [Status::Ordered, false],
            'expired' => [Status::Expired, false],
        ];
    }
}
