<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Source;

use Aheadworks\FeiSpecialPricing\Model\Request\Status as StatusEnum;
use Magento\Framework\Data\OptionSourceInterface;

class Status implements OptionSourceInterface
{
    /**
     * Return request status options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        return array_map(
            static fn (StatusEnum $status): array => ['value' => $status->value, 'label' => $status->label()],
            StatusEnum::cases()
        );
    }
}
