<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor;

class ItemPrices implements ProcessorInterface
{
    public const string KEY = 'special_prices';

    /**
     * Convert dynamic rows ("items") into a map of request item ID => special price.
     *
     * @param array $data
     * @param array $context
     * @return array
     */
    public function process(array $data, array $context = []): array
    {
        $prices = [];
        foreach ((array) ($data['items'] ?? []) as $row) {
            if (!is_array($row) || empty($row['id'])) {
                continue;
            }
            $price = isset($row['special_price']) ? trim((string) $row['special_price']) : '';
            $prices[(int) $row['id']] = $price === '' ? null : str_replace(',', '.', $price);
        }

        $data[self::KEY] = $prices;
        unset($data['items']);

        return $data;
    }
}
