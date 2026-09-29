<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Magento\Framework\Serialize\SerializerInterface;

/**
 * JSON-backed "buy_request" field for models that expose $serializer.
 *
 * @property-read SerializerInterface $serializer
 */
trait BuyRequestDataTrait
{
    /**
     * Return decoded buy request.
     *
     * @return array
     */
    public function getBuyRequest(): array
    {
        $raw = $this->getData(self::BUY_REQUEST);
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        try {
            $decoded = $this->serializer->unserialize($raw);
        } catch (\InvalidArgumentException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Encode and set buy request.
     *
     * @param array $buyRequest
     * @return $this
     */
    public function setBuyRequest(array $buyRequest): self
    {
        return $this->setData(self::BUY_REQUEST, $this->serializer->serialize($buyRequest));
    }
}
