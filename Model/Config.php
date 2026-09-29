<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    public const string XML_PATH_DEFAULT_EXPIRATION_DAYS = 'aw_fei_special_pricing/general/default_expiration_days';
    public const string XML_PATH_EMAIL_SENDER = 'aw_fei_special_pricing/email/sender';
    public const string XML_PATH_FEI_RECIPIENTS = 'aw_fei_special_pricing/email/fei_recipients';
    public const string XML_PATH_TEMPLATE_NEW_REQUEST = 'aw_fei_special_pricing/email/new_request_template';
    public const string XML_PATH_TEMPLATE_APPROVED = 'aw_fei_special_pricing/email/approved_template';
    public const string XML_PATH_TEMPLATE_REJECTED = 'aw_fei_special_pricing/email/rejected_template';

    private const int FALLBACK_EXPIRATION_DAYS = 30;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Return default validity period of approved prices in days.
     *
     * @param int|null $storeId
     * @return int
     */
    public function getDefaultExpirationDays(?int $storeId = null): int
    {
        $value = (int) $this->scopeConfig->getValue(
            self::XML_PATH_DEFAULT_EXPIRATION_DAYS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value > 0 ? $value : self::FALLBACK_EXPIRATION_DAYS;
    }

    /**
     * Return email sender identity code.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSender(?int $storeId = null): string
    {
        return (string) ($this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: 'general');
    }

    /**
     * Return FEI approver email addresses.
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getFeiRecipients(?int $storeId = null): array
    {
        $raw = (string) $this->scopeConfig->getValue(
            self::XML_PATH_FEI_RECIPIENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $emails = array_map('trim', preg_split('/[,;\s]+/', $raw) ?: []);

        return array_values(array_filter(
            $emails,
            static fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        ));
    }

    /**
     * Return email template identifier by config path.
     *
     * @param string $path
     * @param int|null $storeId
     * @return string
     */
    public function getTemplate(string $path, ?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
