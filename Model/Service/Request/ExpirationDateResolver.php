<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ExpirationDateResolver
{
    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Convert a Y-m-d date (store timezone) to UTC end of that day; empty -> null.
     *
     * @param string|null $date
     * @return string|null
     * @throws LocalizedException
     */
    public function resolve(?string $date): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }

        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new LocalizedException(__('Please enter a valid expiration date.'));
        }

        return $this->timezone->convertConfigTimeToUtc($date . ' 23:59:59');
    }
}
