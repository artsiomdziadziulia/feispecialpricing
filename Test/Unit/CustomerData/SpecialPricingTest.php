<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\CustomerData;

use Aheadworks\FeiSpecialPricing\Api\Data\BasketItemInterface;
use Aheadworks\FeiSpecialPricing\CustomerData\SpecialPricing;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\BasketService;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use PHPUnit\Framework\TestCase;

class SpecialPricingTest extends TestCase
{
    /**
     * Denied users get no basket data.
     *
     * @return void
     */
    public function testDeniedUser(): void
    {
        $accessChecker = $this->createMock(AccessChecker::class);
        $accessChecker->method('canUse')->willReturn(false);
        $basketService = $this->createMock(BasketService::class);
        $basketService->expects($this->never())->method('getItems');

        $section = new SpecialPricing(
            $accessChecker,
            $this->createMock(CurrentCompanyUserProvider::class),
            $basketService
        );

        $this->assertSame(['can_use' => false, 'basket_count' => 0], $section->getSectionData());
    }

    /**
     * Allowed users get basket count.
     *
     * @return void
     */
    public function testAllowedUser(): void
    {
        $accessChecker = $this->createMock(AccessChecker::class);
        $accessChecker->method('canUse')->willReturn(true);
        $provider = $this->createMock(CurrentCompanyUserProvider::class);
        $provider->method('getCustomerId')->willReturn(5);
        $basketService = $this->createMock(BasketService::class);
        $basketService->method('getItems')->with(5)->willReturn([
            $this->createMock(BasketItemInterface::class),
            $this->createMock(BasketItemInterface::class),
        ]);

        $section = new SpecialPricing($accessChecker, $provider, $basketService);

        $this->assertSame(['can_use' => true, 'basket_count' => 2], $section->getSectionData());
    }
}
