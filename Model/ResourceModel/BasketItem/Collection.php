<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\ResourceModel\BasketItem;

use Aheadworks\FeiSpecialPricing\Model\BasketItem as BasketItemModel;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\BasketItem as BasketItemResource;
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
        $this->_init(BasketItemModel::class, BasketItemResource::class);
    }
}
