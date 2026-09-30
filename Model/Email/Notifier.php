<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Email;

use Aheadworks\FeiBase\Model\Mail\AttachmentAppender;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Config;
use Aheadworks\FeiSpecialPricing\Model\Pdf\RequestPdfGenerator;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\ContactDataLoader;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface as InlineTranslation;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Notifier
{
    /**
     * @param Config $config
     * @param TransportBuilder $transportBuilder
     * @param InlineTranslation $inlineTranslation
     * @param ContactDataLoader $contactDataLoader
     * @param ItemsRenderer $itemsRenderer
     * @param Formatter $formatter
     * @param UrlInterface $urlBuilder
     * @param RequestPdfGenerator $pdfGenerator
     * @param AttachmentAppender $attachmentAppender
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly InlineTranslation $inlineTranslation,
        private readonly ContactDataLoader $contactDataLoader,
        private readonly ItemsRenderer $itemsRenderer,
        private readonly Formatter $formatter,
        private readonly UrlInterface $urlBuilder,
        private readonly RequestPdfGenerator $pdfGenerator,
        private readonly AttachmentAppender $attachmentAppender,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send a new request to the Special Price Emails and a confirmation to the requester, both with the request PDF.
     *
     * @param RequestInterface $request
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function notifyNewRequest(RequestInterface $request): void
    {
        $this->contactDataLoader->load([$request]);
        $storeId = $request->getStoreId();
        $customerEmail = (string) $request->getCustomerEmail();
        $vars = [
            'request_id' => $request->getId(),
            'company_name' => (string) $request->getCompanyName(),
            'customer_name' => (string) $request->getCustomerName(),
            'customer_email' => $customerEmail,
            'customer_comment' => (string) $request->getCustomerComment(),
            'items_html' => $this->itemsRenderer->render($request),
            'view_url' => $this->getViewUrl($request),
        ];
        $pdf = $this->createPdf($request);

        $recipients = $this->config->getFeiRecipients($storeId);
        if ($recipients !== []) {
            $this->send(
                templateId: $this->config->getTemplate(Config::XML_PATH_TEMPLATE_NEW_REQUEST, $storeId),
                vars: $vars,
                recipients: $recipients,
                storeId: $storeId,
                pdf: $pdf,
                replyTo: $customerEmail !== '' ? [$customerEmail, (string) $request->getCustomerName()] : null
            );
        }

        if ($customerEmail !== '') {
            $this->send(
                templateId: $this->config->getTemplate(Config::XML_PATH_TEMPLATE_SUBMITTED, $storeId),
                vars: $vars,
                recipients: [$customerEmail],
                storeId: $storeId,
                pdf: $pdf
            );
        }
    }

    /**
     * Send the approval / rejection notification to the requester.
     *
     * @param RequestInterface $request
     * @return bool False when the status is not a decision or the requester has no email
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function notifyDecision(RequestInterface $request): bool
    {
        $templatePath = match ($request->getStatus()) {
            Status::Approved->value => Config::XML_PATH_TEMPLATE_APPROVED,
            Status::Rejected->value => Config::XML_PATH_TEMPLATE_REJECTED,
            default => null,
        };
        if ($templatePath === null) {
            return false;
        }

        $this->contactDataLoader->load([$request]);
        $customerEmail = (string) $request->getCustomerEmail();
        if ($customerEmail === '') {
            return false;
        }

        $storeId = $request->getStoreId();
        $vars = [
            'request_id' => $request->getId(),
            'customer_name' => (string) $request->getCustomerName(),
            'admin_comment' => (string) $request->getAdminComment(),
            'expires_at' => $this->formatter->formatDate($request->getExpiresAt(), true),
            'items_html' => $this->itemsRenderer->render($request),
            'view_url' => $this->getViewUrl($request),
        ];

        $this->send(
            templateId: $this->config->getTemplate($templatePath, $storeId),
            vars: $vars,
            recipients: [$customerEmail],
            storeId: $storeId
        );

        return true;
    }

    /**
     * Return the storefront URL of the request in My Account.
     *
     * @param RequestInterface $request
     * @return string
     */
    private function getViewUrl(RequestInterface $request): string
    {
        return $this->urlBuilder->getUrl(
            'aw_fei_sp/request/view',
            ['id' => $request->getId(), '_scope' => $request->getStoreId(), '_nosid' => true]
        );
    }

    /**
     * Generate the request PDF; a failure is logged and the emails go out without the attachment.
     *
     * @param RequestInterface $request
     * @return array|null Keys: content, filename
     */
    private function createPdf(RequestInterface $request): ?array
    {
        try {
            return $this->pdfGenerator->generate($request);
        } catch (\Exception $exception) {
            $this->logger->error(
                'FEI Special Pricing: PDF generation failed for request #' . $request->getId(),
                ['exception' => $exception]
            );

            return null;
        }
    }

    /**
     * Send templated email to recipients.
     *
     * @param string $templateId
     * @param array $vars
     * @param string[] $recipients
     * @param int $storeId
     * @param array|null $pdf Keys: content, filename
     * @param string[]|null $replyTo Email and name
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    private function send(
        string $templateId,
        array $vars,
        array $recipients,
        int $storeId,
        ?array $pdf = null,
        ?array $replyTo = null
    ): void {
        $this->inlineTranslation->suspend();
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars($vars)
                ->setFromByScope($this->config->getSender($storeId), $storeId);
            if ($replyTo !== null) {
                $this->transportBuilder->setReplyTo($replyTo[0], $replyTo[1]);
            }
            foreach ($recipients as $recipient) {
                $this->transportBuilder->addTo($recipient);
            }

            $transport = $this->transportBuilder->getTransport();
            if ($pdf !== null) {
                $this->attachmentAppender->append($transport, $pdf['content'], $pdf['filename']);
            }
            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
