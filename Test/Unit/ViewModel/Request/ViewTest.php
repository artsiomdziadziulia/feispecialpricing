<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\ViewModel\Request;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Service\AccessChecker;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use Aheadworks\FeiSpecialPricing\Model\Service\RequestAvailabilityChecker;
use Aheadworks\FeiSpecialPricing\ViewModel\Request\View;
use Magento\Framework\App\RequestInterface as HttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
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
     * @var View
     */
    private View $viewModel;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $httpRequest = $this->createMock(HttpRequest::class);
        $httpRequest->method('getParam')->with('id')->willReturn('7');
        $this->repositoryMock = $this->createMock(RequestRepositoryInterface::class);
        $provider = $this->createMock(CurrentCompanyUserProvider::class);
        $provider->method('getCustomerId')->willReturn(5);
        $provider->method('getCompanyId')->willReturn(3);
        $this->availabilityCheckerMock = $this->createMock(RequestAvailabilityChecker::class);

        $this->viewModel = new View(
            $httpRequest,
            $this->repositoryMock,
            $provider,
            $this->createMock(AccessChecker::class),
            $this->availabilityCheckerMock,
            $this->createMock(UrlInterface::class)
        );
    }

    /**
     * Foreign request is hidden.
     *
     * @return void
     */
    public function testForeignRequestIsHidden(): void
    {
        $this->repositoryMock->method('getById')->with(7)->willReturn($this->createMock(RequestInterface::class));
        $this->availabilityCheckerMock->method('canView')->willReturn(false);

        $this->assertNull($this->viewModel->getRequest());
        $this->assertFalse($this->viewModel->canAddToCart());
    }

    /**
     * Missing request is hidden and resolved only once.
     *
     * @return void
     */
    public function testMissingRequestIsResolvedOnce(): void
    {
        $this->repositoryMock->expects($this->once())->method('getById')
            ->willThrowException(new NoSuchEntityException());

        $this->assertNull($this->viewModel->getRequest());
        $this->assertNull($this->viewModel->getRequest());
    }

    /**
     * Own approved request can be added to cart.
     *
     * @return void
     */
    public function testOwnApprovedRequest(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $this->repositoryMock->method('getById')->willReturn($request);
        $this->availabilityCheckerMock->method('canView')->willReturn(true);
        $this->availabilityCheckerMock->method('isPurchasable')->with($request, 5)->willReturn(true);

        $this->assertSame($request, $this->viewModel->getRequest());
        $this->assertTrue($this->viewModel->canAddToCart());
    }
}
