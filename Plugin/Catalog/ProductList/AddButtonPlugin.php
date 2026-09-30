<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Plugin\Catalog\ProductList;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;

class AddButtonPlugin
{
    public const string CHILD_ALIAS = 'aw_fei_sp_list_button';

    /**
     * Append the Add to Special Pricing button to a listing item when the list declares the button child block.
     *
     * @param ListProduct $subject
     * @param string $result
     * @param Product $product
     * @return string
     */
    public function afterGetProductDetailsHtml(ListProduct $subject, string $result, Product $product): string
    {
        $button = $subject->getChildBlock(self::CHILD_ALIAS);
        if (!$button) {
            return $result;
        }

        return $result . $button->setData('product', $product)->toHtml();
    }
}
