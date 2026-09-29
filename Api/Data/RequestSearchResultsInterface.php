<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface RequestSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Return requests.
     *
     * @return \Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface[]
     */
    public function getItems();

    /**
     * Set requests.
     *
     * @param \Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
