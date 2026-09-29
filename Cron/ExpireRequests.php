<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Cron;

use Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request as RequestResource;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ExpireRequests
{
    /**
     * @param RequestResource $requestResource
     * @param DateTime $dateTime
     */
    public function __construct(
        private readonly RequestResource $requestResource,
        private readonly DateTime $dateTime
    ) {
    }

    /**
     * Move approved requests with passed expiration date to "expired".
     *
     * @return void
     */
    public function execute(): void
    {
        $this->requestResource->markExpired($this->dateTime->gmtDate('Y-m-d H:i:s'));
    }
}
