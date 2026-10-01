<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\ViewModel;

use Aheadworks\FeiSpecialPricing\ViewModel\AddButton;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AddButtonTest extends TestCase
{
    /**
     * Only saleable products render the button.
     *
     * @return void
     */
    public function testIsRenderable(): void
    {
        $viewModel = new AddButton($this->createMock(UrlInterface::class));
        $saleable = $this->createMock(Product::class);
        $saleable->method('isSaleable')->willReturn(true);
        $notSaleable = $this->createMock(Product::class);
        $notSaleable->method('isSaleable')->willReturn(false);

        $this->assertTrue($viewModel->isRenderable($saleable));
        $this->assertFalse($viewModel->isRenderable($notSaleable));
        $this->assertFalse($viewModel->isRenderable(null));
    }

    /**
     * Composite products and products with required options are sent to the product page from listings.
     *
     * @param bool $isComposite
     * @param bool $hasRequiredOptions
     * @param string $expected
     * @return void
     */
    #[DataProvider('optionsPageProvider')]
    public function testGetOptionsPageUrl(bool $isComposite, bool $hasRequiredOptions, string $expected): void
    {
        $type = $this->createMock(AbstractType::class);
        $type->method('hasRequiredOptions')->willReturn($hasRequiredOptions);
        $product = $this->createMock(Product::class);
        $product->method('isComposite')->willReturn($isComposite);
        $product->method('getTypeInstance')->willReturn($type);
        $product->method('getProductUrl')->willReturn('https://store/bag.html');

        $viewModel = new AddButton($this->createMock(UrlInterface::class));

        $this->assertSame($expected, $viewModel->getOptionsPageUrl($product));
    }

    /**
     * Product kinds.
     *
     * @return array
     */
    public static function optionsPageProvider(): array
    {
        return [
            'simple' => [false, false, ''],
            'configurable / bundle / grouped' => [true, false, 'https://store/bag.html'],
            'simple with required custom options' => [false, true, 'https://store/bag.html'],
        ];
    }

    /**
     * Configurable products without required custom options expose their attribute ids for quick add.
     *
     * @return void
     */
    public function testGetQuickAddAttributeIdsForConfigurable(): void
    {
        $product = $this->createMock(Product::class);
        $type = $this->createMock(Configurable::class);
        $type->method('hasRequiredOptions')->willReturn(false);
        $type->method('getConfigurableAttributes')->with($product)->willReturn([
            $this->createAttribute('93'),
            $this->createAttribute('144'),
        ]);
        $product->method('getTypeInstance')->willReturn($type);

        $viewModel = new AddButton($this->createMock(UrlInterface::class));

        $this->assertSame([93, 144], $viewModel->getQuickAddAttributeIds($product));
    }

    /**
     * Configurable products with required custom options always go to the product page.
     *
     * @return void
     */
    public function testGetQuickAddAttributeIdsForConfigurableWithRequiredOptions(): void
    {
        $type = $this->createMock(Configurable::class);
        $type->method('hasRequiredOptions')->willReturn(true);
        $type->expects($this->never())->method('getConfigurableAttributes');
        $product = $this->createMock(Product::class);
        $product->method('getTypeInstance')->willReturn($type);

        $viewModel = new AddButton($this->createMock(UrlInterface::class));

        $this->assertSame([], $viewModel->getQuickAddAttributeIds($product));
    }

    /**
     * Non-configurable products have no quick add attributes.
     *
     * @return void
     */
    public function testGetQuickAddAttributeIdsForOtherTypes(): void
    {
        $product = $this->createMock(Product::class);
        $product->method('getTypeInstance')->willReturn($this->createMock(AbstractType::class));

        $viewModel = new AddButton($this->createMock(UrlInterface::class));

        $this->assertSame([], $viewModel->getQuickAddAttributeIds($product));
    }

    /**
     * Build a configurable attribute stub.
     *
     * @param string $attributeId
     * @return Attribute
     */
    private function createAttribute(string $attributeId): Attribute
    {
        $attribute = $this->createMock(Attribute::class);
        $attribute->method('getAttributeId')->willReturn($attributeId);

        return $attribute;
    }
}
