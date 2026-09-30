<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Pdf;

use Aheadworks\FeiBase\Model\Pdf\HtmlToPdfConverter;
use Aheadworks\FeiBase\Model\Pdf\StoreLogoResolver;
use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;

class RequestPdfGenerator
{
    public const string TEMPLATE = 'Aheadworks_FeiSpecialPricing::pdf/request.phtml';

    /**
     * @param HtmlToPdfConverter $converter
     * @param StoreLogoResolver $storeLogoResolver
     * @param StoreManagerInterface $storeManager
     * @param Formatter $formatter
     * @param LayoutInterface $layout
     */
    public function __construct(
        private readonly HtmlToPdfConverter $converter,
        private readonly StoreLogoResolver $storeLogoResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly Formatter $formatter,
        private readonly LayoutInterface $layout
    ) {
    }

    /**
     * Generate the PDF of a special pricing request (contact data must be loaded on the request).
     *
     * @param RequestInterface $request
     * @return array{content: string, filename: string}
     * @throws LocalizedException
     */
    public function generate(RequestInterface $request): array
    {
        $storeId = $request->getStoreId();

        /** @var Template $block */
        $block = $this->layout->createBlock(Template::class);
        $html = $block->setTemplate(self::TEMPLATE)
            ->setData('request', $request)
            ->setData('formatter', $this->formatter)
            ->setData('store_name', (string) $this->storeManager->getStore($storeId)->getFrontendName())
            ->setData('logo_path', $this->storeLogoResolver->getPath($storeId))
            ->setData('regular_total', $this->getRegularTotal($request))
            ->setData('date', $this->formatter->formatDate($request->getCreatedAt() ?: gmdate('Y-m-d H:i:s'), true))
            ->toHtml();

        return [
            'content' => $this->converter->convert($html),
            'filename' => sprintf('special-pricing-request-%d.pdf', (int) $request->getId()),
        ];
    }

    /**
     * Return the request value at regular prices.
     *
     * @param RequestInterface $request
     * @return float
     */
    private function getRegularTotal(RequestInterface $request): float
    {
        $total = 0.0;
        foreach ($request->getItems() as $item) {
            $total += (float) $item->getOriginalPrice() * $item->getQty();
        }

        return $total;
    }
}
