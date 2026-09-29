<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\ViewModel;

use Aheadworks\FeiSpecialPricing\ViewModel\AddButton;
use Magento\Catalog\Model\Product;
use Magento\Framework\UrlInterface;
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
}
