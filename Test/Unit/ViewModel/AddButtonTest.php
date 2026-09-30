<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\ViewModel;

use Aheadworks\FeiSpecialPricing\ViewModel\AddButton;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type\AbstractType;
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
}
