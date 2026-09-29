<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Email;

use Aheadworks\Ca\Api\CompanyRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Model\Config;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Backend\Model\UrlInterface as BackendUrl;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface as InlineTranslation;
use Magento\Framework\UrlInterface;

class Notifier
{
    /**
     * @param Config $config
     * @param TransportBuilder $transportBuilder
     * @param InlineTranslation $inlineTranslation
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param ItemsRenderer $itemsRenderer
     * @param Formatter $formatter
     * @param UrlInterface $urlBuilder
     * @param BackendUrl $backendUrl
     */
    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly InlineTranslation $inlineTranslation,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly ItemsRenderer $itemsRenderer,
        private readonly Formatter $formatter,
        private readonly UrlInterface $urlBuilder,
        private readonly BackendUrl $backendUrl
    ) {
    }

    /**
     * Send a new request notification to FEI approvers.
     *
     * @param RequestInterface $request
     * @return bool False when no recipients are configured
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    public function notifyNewRequest(RequestInterface $request): bool
    {
        $storeId = $request->getStoreId();
        $recipients = $this->config->getFeiRecipients($storeId);
        if ($recipients === []) {
            return false;
        }

        $customer = $this->customerRepository->getById($request->getCustomerId());
        $vars = [
            'request_id' => $request->getId(),
            'company_name' => (string) $this->companyRepository->get($request->getCompanyId())->getName(),
            'customer_name' => trim($customer->getFirstname() . ' ' . $customer->getLastname()),
            'customer_email' => $customer->getEmail(),
            'customer_comment' => (string) $request->getCustomerComment(),
            'items_html' => $this->itemsRenderer->render($request),
            'review_url' => $this->backendUrl->getUrl(
                'aw_fei_sp_admin/request/edit',
                ['id' => $request->getId(), '_nosecret' => true]
            ),
        ];

        $this->send(
            $this->config->getTemplate(Config::XML_PATH_TEMPLATE_NEW_REQUEST, $storeId),
            $vars,
            $recipients,
            $storeId
        );

        return true;
    }

    /**
     * Send the approval / rejection notification to the requester.
     *
     * @param RequestInterface $request
     * @return bool False when the status is not a decision
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

        $storeId = $request->getStoreId();
        $customer = $this->customerRepository->getById($request->getCustomerId());
        $vars = [
            'request_id' => $request->getId(),
            'customer_name' => trim($customer->getFirstname() . ' ' . $customer->getLastname()),
            'admin_comment' => (string) $request->getAdminComment(),
            'expires_at' => $this->formatter->formatDate($request->getExpiresAt(), true),
            'items_html' => $this->itemsRenderer->render($request),
            'view_url' => $this->urlBuilder->getUrl(
                'aw_fei_sp/request/view',
                ['id' => $request->getId(), '_scope' => $storeId, '_nosid' => true]
            ),
        ];

        $this->send(
            $this->config->getTemplate($templatePath, $storeId),
            $vars,
            [(string) $customer->getEmail()],
            $storeId
        );

        return true;
    }

    /**
     * Send templated email to recipients.
     *
     * @param string $templateId
     * @param array $vars
     * @param string[] $recipients
     * @param int $storeId
     * @return void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\MailException
     */
    private function send(string $templateId, array $vars, array $recipients, int $storeId): void
    {
        $this->inlineTranslation->suspend();
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars($vars)
                ->setFromByScope($this->config->getSender($storeId), $storeId);
            foreach ($recipients as $recipient) {
                $this->transportBuilder->addTo($recipient);
            }
            $this->transportBuilder->getTransport()->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
