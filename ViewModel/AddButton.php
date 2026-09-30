<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class AddButton implements ArgumentInterface
{
    /**
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * Check whether the button markup is rendered; user access is resolved client-side (FPC-safe).
     *
     * @param Product|null $product
     * @return bool
     */
    public function isRenderable(?Product $product): bool
    {
        return $product !== null && $product->isSaleable();
    }

    /**
     * Return the product page URL when options must be chosen there (listing button); empty otherwise.
     *
     * @param Product $product
     * @return string
     */
    public function getOptionsPageUrl(Product $product): string
    {
        $requiresOptions = $product->isComposite() || $product->getTypeInstance()->hasRequiredOptions($product);

        return $requiresOptions ? (string) $product->getProductUrl() : '';
    }

    /**
     * Return AJAX add URL.
     *
     * @return string
     */
    public function getAddUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/basket/add', ['_secure' => true]);
    }

    /**
     * Return basket page URL.
     *
     * @return string
     */
    public function getBasketUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/basket/index', ['_secure' => true]);
    }
}
