<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Email;

use Aheadworks\Ca\Api\CompanyRepositoryInterface;
use Aheadworks\Ca\Api\Data\CompanyInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Config;
use Aheadworks\FeiSpecialPricing\Model\Email\ItemsRenderer;
use Aheadworks\FeiSpecialPricing\Model\Email\Notifier;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotifierTest extends TestCase
{
    /**
     * @var Config&MockObject
     */
    private MockObject $configMock;

    /**
     * @var TransportBuilder&MockObject
     */
    private MockObject $transportBuilderMock;

    /**
     * @var Notifier
     */
    private Notifier $notifier;

    /**
     * Set up test dependencies.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->configMock->method('getTemplate')->willReturnArgument(0);
        $this->configMock->method('getSender')->willReturn('general');

        $this->transportBuilderMock = $this->createMock(TransportBuilder::class);
        foreach (['setTemplateIdentifier', 'setTemplateOptions', 'setTemplateVars', 'setFromByScope', 'addTo'] as $m) {
            $this->transportBuilderMock->method($m)->willReturnSelf();
        }
        $this->transportBuilderMock->method('getTransport')->willReturn($this->createMock(TransportInterface::class));

        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getFirstname')->willReturn('John');
        $customer->method('getLastname')->willReturn('Doe');
        $customer->method('getEmail')->willReturn('john@example.com');
        $customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $customerRepository->method('getById')->willReturn($customer);

        $company = $this->createMock(CompanyInterface::class);
        $company->method('getName')->willReturn('Agency');
        $companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $companyRepository->method('get')->willReturn($company);

        $this->notifier = new Notifier(
            $this->configMock,
            $this->transportBuilderMock,
            $this->createMock(StateInterface::class),
            $customerRepository,
            $companyRepository,
            $this->createMock(ItemsRenderer::class),
            $this->createMock(Formatter::class),
            $this->createMock(UrlInterface::class)
        );
    }

    /**
     * No FEI recipients - nothing is sent.
     *
     * @return void
     */
    public function testNewRequestWithoutRecipients(): void
    {
        $this->configMock->method('getFeiRecipients')->willReturn([]);
        $this->transportBuilderMock->expects($this->never())->method('getTransport');

        $this->assertFalse($this->notifier->notifyNewRequest($this->createRequest(Status::Pending)));
    }

    /**
     * Each FEI recipient is added.
     *
     * @return void
     */
    public function testNewRequestSendsToAllRecipients(): void
    {
        $this->configMock->method('getFeiRecipients')->willReturn(['a@fei.com', 'b@fei.com']);
        $this->transportBuilderMock->expects($this->exactly(2))->method('addTo');
        $this->transportBuilderMock->expects($this->once())->method('setTemplateIdentifier')
            ->with(Config::XML_PATH_TEMPLATE_NEW_REQUEST);

        $this->assertTrue($this->notifier->notifyNewRequest($this->createRequest(Status::Pending)));
    }

    /**
     * Approved / rejected use their templates; other statuses send nothing.
     *
     * @return void
     */
    public function testDecisionTemplates(): void
    {
        $this->transportBuilderMock->expects($this->exactly(2))->method('setTemplateIdentifier')
            ->willReturnCallback(function (string $template) {
                static $expected = [Config::XML_PATH_TEMPLATE_APPROVED, Config::XML_PATH_TEMPLATE_REJECTED];
                $this->assertSame(array_shift($expected), $template);

                return $this->transportBuilderMock;
            });
        $this->transportBuilderMock->method('addTo')->with('john@example.com')->willReturnSelf();

        $this->assertTrue($this->notifier->notifyDecision($this->createRequest(Status::Approved)));
        $this->assertTrue($this->notifier->notifyDecision($this->createRequest(Status::Rejected)));
        $this->assertFalse($this->notifier->notifyDecision($this->createRequest(Status::Ordered)));
    }

    /**
     * Build request stub.
     *
     * @param Status $status
     * @return RequestInterface
     */
    private function createRequest(Status $status): RequestInterface
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getId')->willReturn(1);
        $request->method('getStoreId')->willReturn(1);
        $request->method('getCustomerId')->willReturn(5);
        $request->method('getCompanyId')->willReturn(3);
        $request->method('getStatus')->willReturn($status->value);

        return $request;
    }
}
