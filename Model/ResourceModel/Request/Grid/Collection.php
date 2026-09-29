<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\ResourceModel\Request\Grid;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * Join company and customer data for the admin grid.
     *
     * @return $this
     */
    #[\Override]
    protected function _initSelect()
    {
        parent::_initSelect();

        $connection = $this->getConnection();
        $this->getSelect()
            ->joinLeft(
                ['company' => $this->getTable('aw_ca_company')],
                'company.id = main_table.company_id',
                ['company_name' => 'company.name']
            )
            ->joinLeft(
                ['customer' => $this->getTable('customer_entity')],
                'customer.entity_id = main_table.customer_id',
                [
                    'customer_email' => 'customer.email',
                    'customer_name' => $connection->getConcatSql(['customer.firstname', 'customer.lastname'], ' '),
                ]
            )
            ->joinLeft(
                ['items' => $this->getItemsCountSelect()],
                'items.request_id = main_table.id',
                ['items_count' => 'items.items_count']
            );

        $this->addFilterToMap('id', 'main_table.id');
        $this->addFilterToMap('status', 'main_table.status');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('company_name', 'company.name');
        $this->addFilterToMap('customer_email', 'customer.email');

        return $this;
    }

    /**
     * Build sub-select with item count per request.
     *
     * @return \Magento\Framework\DB\Select
     */
    private function getItemsCountSelect(): \Magento\Framework\DB\Select
    {
        return $this->getConnection()->select()
            ->from(
                $this->getTable('aw_fei_sp_request_item'),
                ['request_id', 'items_count' => new Expression('COUNT(*)')]
            )
            ->group('request_id');
    }
}
