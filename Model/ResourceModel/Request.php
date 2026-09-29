<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\ResourceModel;

use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Request extends AbstractDb
{
    public const string MAIN_TABLE = 'aw_fei_sp_request';

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::MAIN_TABLE, 'id');
    }

    /**
     * Move approved requests with passed expiration date to "expired".
     *
     * @param string $now Current UTC datetime (Y-m-d H:i:s)
     * @return int Number of affected rows
     */
    public function markExpired(string $now): int
    {
        return $this->getConnection()->update(
            $this->getMainTable(),
            ['status' => Status::Expired->value],
            [
                'status = ?' => Status::Approved->value,
                'expires_at IS NOT NULL',
                'expires_at < ?' => $now,
            ]
        );
    }
}
