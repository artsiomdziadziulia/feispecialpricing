<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request;

use Aheadworks\FeiSpecialPricing\Model\Request as RequestModel;
use Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request as RequestResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Initialize collection.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(RequestModel::class, RequestResource::class);
    }
}
