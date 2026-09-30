<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Email;

use Aheadworks\FeiBase\Model\Mail\AttachmentAppender;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Config;
use Aheadworks\FeiSpecialPricing\Model\Email\ItemsRenderer;
use Aheadworks\FeiSpecialPricing\Model\Email\Notifier;
use Aheadworks\FeiSpecialPricing\Model\Pdf\RequestPdfGenerator;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\ContactDataLoader;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class NotifierTest extends TestCase
{
    private const array PDF = ['content' => '%PDF', 'filename' => 'special-pricing-request-1.pdf'];

    /**
     * @var Config&MockObject
     */
    private MockObject $configMock;

    /**
     * @var TransportBuilder&MockObject
     */
    private MockObject $transportBuilderMock;

    /**
     * @var RequestPdfGenerator&MockObject
     */
    private MockObject $pdfGeneratorMock;

    /**
     * @var AttachmentAppender&MockObject
     */
    private MockObject $attachmentAppenderMock;

    /**
     * @var LoggerInterface&MockObject
     */
    private MockObject $loggerMock;

    /**
     * @var string[] Template IDs in sending order
     */
    private array $sentTemplates = [];

    /**
     * @var string[] Recipients in adding order
     */
    private array $recipients = [];

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
        foreach (['setTemplateOptions', 'setTemplateVars', 'setFromByScope', 'setReplyTo'] as $method) {
            $this->transportBuilderMock->method($method)->willReturnSelf();
        }
        $this->transportBuilderMock->method('setTemplateIdentifier')->willReturnCallback(
            function (string $template) {
                $this->sentTemplates[] = $template;

                return $this->transportBuilderMock;
            }
        );
        $this->transportBuilderMock->method('addTo')->willReturnCallback(function (string $email) {
            $this->recipients[] = $email;

            return $this->transportBuilderMock;
        });
        $this->transportBuilderMock->method('getTransport')->willReturn($this->createMock(TransportInterface::class));

        $this->pdfGeneratorMock = $this->createMock(RequestPdfGenerator::class);
        $this->attachmentAppenderMock = $this->createMock(AttachmentAppender::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturn('https://store/aw_fei_sp/request/view/id/1/');

        $this->notifier = new Notifier(
            $this->configMock,
            $this->transportBuilderMock,
            $this->createMock(StateInterface::class),
            $this->createMock(ContactDataLoader::class),
            $this->createMock(ItemsRenderer::class),
            $this->createMock(Formatter::class),
            $urlBuilder,
            $this->pdfGeneratorMock,
            $this->attachmentAppenderMock,
            $this->loggerMock
        );
    }

    /**
     * New request goes to every Special Price Email and a confirmation to the requester, both with the PDF.
     *
     * @return void
     */
    public function testNewRequestSendsToFeiAndRequesterWithPdf(): void
    {
        $this->configMock->method('getFeiRecipients')->willReturn(['a@fei.com', 'b@fei.com']);
        $this->pdfGeneratorMock->expects($this->once())->method('generate')->willReturn(self::PDF);
        $this->attachmentAppenderMock->expects($this->exactly(2))->method('append')
            ->with($this->anything(), '%PDF', 'special-pricing-request-1.pdf');
        $this->transportBuilderMock->expects($this->once())->method('setReplyTo')
            ->with('john@example.com', 'John Doe');

        $this->notifier->notifyNewRequest($this->createRequest(Status::Pending));

        $this->assertSame(
            [Config::XML_PATH_TEMPLATE_NEW_REQUEST, Config::XML_PATH_TEMPLATE_SUBMITTED],
            $this->sentTemplates
        );
        $this->assertSame(['a@fei.com', 'b@fei.com', 'john@example.com'], $this->recipients);
    }

    /**
     * Without Special Price Emails only the requester confirmation is sent.
     *
     * @return void
     */
    public function testNewRequestWithoutFeiRecipientsSendsOnlyConfirmation(): void
    {
        $this->configMock->method('getFeiRecipients')->willReturn([]);
        $this->pdfGeneratorMock->method('generate')->willReturn(self::PDF);

        $this->notifier->notifyNewRequest($this->createRequest(Status::Pending));

        $this->assertSame([Config::XML_PATH_TEMPLATE_SUBMITTED], $this->sentTemplates);
        $this->assertSame(['john@example.com'], $this->recipients);
    }

    /**
     * PDF failure is logged and the emails are still sent, without the attachment.
     *
     * @return void
     */
    public function testNewRequestPdfFailureSendsWithoutAttachment(): void
    {
        $this->configMock->method('getFeiRecipients')->willReturn(['a@fei.com']);
        $this->pdfGeneratorMock->method('generate')->willThrowException(new LocalizedException(__('fail')));
        $this->loggerMock->expects($this->once())->method('error');
        $this->attachmentAppenderMock->expects($this->never())->method('append');

        $this->notifier->notifyNewRequest($this->createRequest(Status::Pending));

        $this->assertCount(2, $this->sentTemplates);
    }

    /**
     * Approved / rejected use their templates without PDF; other statuses send nothing.
     *
     * @return void
     */
    public function testDecisionTemplates(): void
    {
        $this->attachmentAppenderMock->expects($this->never())->method('append');

        $this->assertTrue($this->notifier->notifyDecision($this->createRequest(Status::Approved)));
        $this->assertTrue($this->notifier->notifyDecision($this->createRequest(Status::Rejected)));
        $this->assertFalse($this->notifier->notifyDecision($this->createRequest(Status::Ordered)));

        $this->assertSame(
            [Config::XML_PATH_TEMPLATE_APPROVED, Config::XML_PATH_TEMPLATE_REJECTED],
            $this->sentTemplates
        );
        $this->assertSame(['john@example.com', 'john@example.com'], $this->recipients);
    }

    /**
     * Requester without email gets no decision email.
     *
     * @return void
     */
    public function testDecisionWithoutCustomerEmail(): void
    {
        $this->assertFalse($this->notifier->notifyDecision($this->createRequest(Status::Approved, null)));
        $this->assertSame([], $this->sentTemplates);
    }

    /**
     * Build request stub with loaded contact data.
     *
     * @param Status $status
     * @param string|null $email
     * @return RequestInterface
     */
    private function createRequest(Status $status, ?string $email = 'john@example.com'): RequestInterface
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getId')->willReturn(1);
        $request->method('getStoreId')->willReturn(1);
        $request->method('getCustomerId')->willReturn(5);
        $request->method('getCompanyId')->willReturn(3);
        $request->method('getStatus')->willReturn($status->value);
        $request->method('getCustomerName')->willReturn('John Doe');
        $request->method('getCustomerEmail')->willReturn($email);
        $request->method('getCompanyName')->willReturn('Agency');

        return $request;
    }
}
