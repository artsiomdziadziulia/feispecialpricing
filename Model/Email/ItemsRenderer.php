<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Email;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\ViewModel\Formatter;
use Magento\Framework\Escaper;

class ItemsRenderer
{
    /**
     * @param Escaper $escaper
     * @param Formatter $formatter
     */
    public function __construct(
        private readonly Escaper $escaper,
        private readonly Formatter $formatter
    ) {
    }

    /**
     * Render escaped HTML table of request items for emails.
     *
     * @param RequestInterface $request
     * @return string
     */
    public function render(RequestInterface $request): string
    {
        $headers = [__('Product'), __('SKU'), __('Qty'), __('Regular Price'), __('Special Price')];
        $html = '<table cellpadding="6" cellspacing="0" border="1" style="border-collapse:collapse;width:100%">'
            . '<thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th align="left">' . $this->escaper->escapeHtml((string) $header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($request->getItems() as $item) {
            $cells = [
                $item->getName(),
                $item->getSku(),
                $this->formatter->formatQty($item->getQty()),
                $this->formatter->formatPrice($item->getOriginalPrice()),
                $this->formatter->formatPrice($item->getSpecialPrice()),
            ];
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= '<td>' . $this->escaper->escapeHtml($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }
}
