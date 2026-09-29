<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BasketItem extends AbstractDb
{
    public const string MAIN_TABLE = 'aw_fei_sp_basket_item';

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
     * Delete all basket rows of a customer.
     *
     * @param int $customerId
     * @return void
     */
    public function deleteByCustomerId(int $customerId): void
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            ['customer_id = ?' => $customerId]
        );
    }
}
