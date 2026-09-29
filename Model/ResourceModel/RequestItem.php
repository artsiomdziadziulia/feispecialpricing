<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RequestItem extends AbstractDb
{
    public const string MAIN_TABLE = 'aw_fei_sp_request_item';

    /**
     * Initialize resource model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::MAIN_TABLE, 'id');
    }
}
