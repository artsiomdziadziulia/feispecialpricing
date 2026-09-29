<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Basket;

class BuyRequestNormalizer
{
    private const array TRANSIENT_KEYS = [
        'form_key',
        'uenc',
        'qty',
        'product',
        'item',
        'related_product',
        'return_url',
        'selected_configurable_option',
        'isAjax',
    ];

    /**
     * Strip request-specific keys so only product options remain.
     *
     * @param array $buyRequest
     * @return array
     */
    public function normalize(array $buyRequest): array
    {
        $options = array_diff_key($buyRequest, array_flip(self::TRANSIENT_KEYS));
        $options = array_filter(
            $options,
            static fn ($value): bool => $value !== '' && $value !== null && $value !== []
        );
        ksort($options);

        return $options;
    }

    /**
     * Check whether two normalized buy requests describe the same product configuration.
     *
     * @param array $left
     * @param array $right
     * @return bool
     */
    public function isSame(array $left, array $right): bool
    {
        return $this->normalize($left) == $this->normalize($right);
    }
}
