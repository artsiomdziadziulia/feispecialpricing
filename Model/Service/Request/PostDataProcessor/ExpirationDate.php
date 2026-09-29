<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ExpirationDate implements ProcessorInterface
{
    public const string KEY = 'expires_at';

    /**
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Convert the admin date (store timezone) to UTC end of that day; empty -> null.
     *
     * @param array $data
     * @param array $context
     * @return array
     * @throws LocalizedException
     */
    public function process(array $data, array $context = []): array
    {
        $raw = trim((string) ($data[self::KEY] ?? ''));
        if ($raw === '') {
            $data[self::KEY] = null;

            return $data;
        }

        $data[self::KEY] = $this->timezone->convertConfigTimeToUtc($this->normalize($raw) . ' 23:59:59');

        return $data;
    }

    /**
     * Normalize a strict Y-m-d or locale-formatted date picker value to Y-m-d.
     *
     * @param string $raw
     * @return string
     * @throws LocalizedException
     */
    private function normalize(string $raw): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $date = \DateTime::createFromFormat('!Y-m-d', $raw);
            if ($date === false || $date->format('Y-m-d') !== $raw) {
                throw new LocalizedException(__('Please enter a valid expiration date.'));
            }

            return $raw;
        }

        try {
            return $this->timezone->date($raw, null, false, false)->format('Y-m-d');
        } catch (\Exception) {
            throw new LocalizedException(__('Please enter a valid expiration date.'));
        }
    }
}
