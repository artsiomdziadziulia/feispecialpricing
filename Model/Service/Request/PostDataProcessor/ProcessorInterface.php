<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor;

use Magento\Framework\Exception\LocalizedException;

interface ProcessorInterface
{
    /**
     * Normalize admin form post data of a special pricing request.
     *
     * @param array $data
     * @param array $context
     * @return array
     * @throws LocalizedException
     */
    public function process(array $data, array $context = []): array;
}
