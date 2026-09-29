<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem;

use Aheadworks\FeiSpecialPricing\Model\RequestItem as RequestItemModel;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\RequestItem as RequestItemResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(RequestItemModel::class, RequestItemResource::class);
    }
}
