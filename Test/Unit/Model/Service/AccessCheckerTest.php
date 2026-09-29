<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service;

use Aheadworks\Ca\Api\AuthorizationManagementInterface;
use Aheadworks\FeiCa\Model\RestrictionBypassChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use PHPUnit\Framework\TestCase;

class AccessCheckerTest extends TestCase
{
    /**
     * Access requires company membership and the company ACL resource.
     *
     * @dataProvider canUseProvider
     * @param int|null $companyId
     * @param bool $allowed
     * @param bool $expected
     * @return void
     */
    public function testCanUse(?int $companyId, bool $allowed, bool $expected): void
    {
        $provider = $this->createMock(CurrentCompanyUserProvider::class);
        $provider->method('getCompanyId')->willReturn($companyId);
        $authorization = $this->createMock(AuthorizationManagementInterface::class);
        $authorization->method('isAllowedByResource')->with(AccessChecker::ACL_RESOURCE)->willReturn($allowed);

        $checker = new AccessChecker($provider, $authorization, $this->createMock(RestrictionBypassChecker::class));

        $this->assertSame($expected, $checker->canUse());
    }

    /**
     * Data for testCanUse.
     *
     * @return array
     */
    public static function canUseProvider(): array
    {
        return [
            'company user with permission' => [3, true, true],
            'company user without permission' => [3, false, false],
            'not a company user' => [null, true, false],
        ];
    }
}
