<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor;

use Magento\Framework\Exception\LocalizedException;

class Composite implements ProcessorInterface
{
    /**
     * @param ProcessorInterface[] $processors
     */
    public function __construct(
        private readonly array $processors = []
    ) {
    }

    /**
     * Run all registered processors in order.
     *
     * @param array $data
     * @param array $context
     * @return array
     * @throws LocalizedException
     */
    public function process(array $data, array $context = []): array
    {
        foreach ($this->processors as $processor) {
            if ($processor instanceof ProcessorInterface) {
                $data = $processor->process($data, $context);
            }
        }

        return $data;
    }
}
