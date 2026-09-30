<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Pdf;

use Aheadworks\FeiBase\Model\Pdf\HtmlToPdfConverter;
use Aheadworks\FeiBase\Model\Pdf\StoreLogoResolver;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestItemInterface;
use Aheadworks\FeiSpecialPricing\Model\Pdf\RequestPdfGenerator;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

class RequestPdfGeneratorTest extends TestCase
{
    /**
     * The request is rendered through the template with store data and regular total, then converted.
     *
     * @return void
     */
    public function testGenerateRendersAndConverts(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getId')->willReturn(12);
        $request->method('getStoreId')->willReturn(1);
        $request->method('getItems')->willReturn([
            $this->createItem(2.0, 10.0),
            $this->createItem(1.0, null),
            $this->createItem(3.0, 5.5),
        ]);

        $store = $this->createMock(Store::class);
        $store->method('getFrontendName')->willReturn('FEI Store');
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->with(1)->willReturn($store);
        $logoResolver = $this->createMock(StoreLogoResolver::class);
        $logoResolver->method('getPath')->with(1)->willReturn('/media/logo.png');
        $formatter = $this->createMock(Formatter::class);
        $formatter->method('formatDate')->willReturn('Sep 30, 2026');

        $data = [];
        $block = $this->createMock(Template::class);
        $block->method('setTemplate')->with(RequestPdfGenerator::TEMPLATE)->willReturnSelf();
        $block->method('setData')->willReturnCallback(function (string $key, $value) use (&$data, $block) {
            $data[$key] = $value;

            return $block;
        });
        $block->method('toHtml')->willReturn('<html></html>');
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('createBlock')->with(Template::class)->willReturn($block);

        $converter = $this->createMock(HtmlToPdfConverter::class);
        $converter->expects($this->once())->method('convert')->with('<html></html>')->willReturn('%PDF');

        $result = (new RequestPdfGenerator($converter, $logoResolver, $storeManager, $formatter, $layout))
            ->generate($request);

        $this->assertSame(['content' => '%PDF', 'filename' => 'special-pricing-request-12.pdf'], $result);
        $this->assertSame($request, $data['request']);
        $this->assertSame('FEI Store', $data['store_name']);
        $this->assertSame('/media/logo.png', $data['logo_path']);
        $this->assertSame(36.5, $data['regular_total']);
        $this->assertSame('Sep 30, 2026', $data['date']);
    }

    /**
     * Create request item stub.
     *
     * @param float $qty
     * @param float|null $price
     * @return RequestItemInterface
     */
    private function createItem(float $qty, ?float $price): RequestItemInterface
    {
        $item = $this->createMock(RequestItemInterface::class);
        $item->method('getQty')->willReturn($qty);
        $item->method('getOriginalPrice')->willReturn($price);

        return $item;
    }
}
