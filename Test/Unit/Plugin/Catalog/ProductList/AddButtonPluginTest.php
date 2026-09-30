<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Plugin\Catalog\ProductList;

use Aheadworks\FeiSpecialPricing\Plugin\Catalog\ProductList\AddButtonPlugin;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\TestCase;

class AddButtonPluginTest extends TestCase
{
    /**
     * The button child block is rendered for the product and appended to the details HTML.
     *
     * @return void
     */
    public function testAppendsButtonHtml(): void
    {
        $product = $this->createMock(Product::class);
        $button = $this->createMock(Template::class);
        $button->expects($this->once())->method('setData')->with('product', $product)->willReturnSelf();
        $button->method('toHtml')->willReturn('<button/>');
        $list = $this->createMock(ListProduct::class);
        $list->method('getChildBlock')->with(AddButtonPlugin::CHILD_ALIAS)->willReturn($button);

        $this->assertSame(
            '<swatches/><button/>',
            (new AddButtonPlugin())->afterGetProductDetailsHtml($list, '<swatches/>', $product)
        );
    }

    /**
     * Lists without the button child block (other pages, widgets) are left untouched.
     *
     * @return void
     */
    public function testKeepsHtmlWithoutChildBlock(): void
    {
        $list = $this->createMock(ListProduct::class);
        $list->method('getChildBlock')->willReturn(false);

        $this->assertSame(
            '<swatches/>',
            (new AddButtonPlugin())->afterGetProductDetailsHtml($list, '<swatches/>', $this->createMock(Product::class))
        );
    }
}
