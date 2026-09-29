<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service\Request\PostDataProcessor;

class AdminComment implements ProcessorInterface
{
    public const string KEY = 'admin_comment';

    private const int MAX_LENGTH = 5000;

    /**
     * Trim and limit the FEI comment; empty -> null.
     *
     * @param array $data
     * @param array $context
     * @return array
     */
    public function process(array $data, array $context = []): array
    {
        $comment = mb_substr(trim((string) ($data[self::KEY] ?? '')), 0, self::MAX_LENGTH);
        $data[self::KEY] = $comment !== '' ? $comment : null;

        return $data;
    }
}
