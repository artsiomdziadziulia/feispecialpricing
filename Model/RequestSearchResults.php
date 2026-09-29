<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class RequestSearchResults extends SearchResults implements RequestSearchResultsInterface
{
}
