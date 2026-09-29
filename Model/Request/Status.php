<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Request;

enum Status: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Ordered = 'ordered';

    /**
     * Return human-readable label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => (string) __('Pending'),
            self::Approved => (string) __('Approved'),
            self::Rejected => (string) __('Rejected'),
            self::Expired => (string) __('Expired'),
            self::Ordered => (string) __('Ordered'),
        };
    }
}
