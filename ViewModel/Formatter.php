<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\ViewModel;

use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Formatter implements ArgumentInterface
{
    /**
     * @param PriceCurrencyInterface $priceCurrency
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Format amount in the current store currency (plain text, no HTML).
     *
     * @param float|null $amount
     * @return string
     */
    public function formatPrice(?float $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        return $this->priceCurrency->format($amount, false);
    }

    /**
     * Format UTC datetime in the store timezone.
     *
     * @param string|null $utcDate
     * @param bool $withTime
     * @return string
     */
    public function formatDate(?string $utcDate, bool $withTime = false): string
    {
        if ($utcDate === null || $utcDate === '') {
            return '—';
        }

        return $this->timezone->formatDateTime(
            $utcDate,
            \IntlDateFormatter::MEDIUM,
            $withTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE
        );
    }

    /**
     * Return status label.
     *
     * @param string $status
     * @return string
     */
    public function getStatusLabel(string $status): string
    {
        return Status::tryFrom($status)?->label() ?? $status;
    }

    /**
     * Format qty without trailing zeros.
     *
     * @param float $qty
     * @return string
     */
    public function formatQty(float $qty): string
    {
        return (string) ($qty + 0);
    }
}
