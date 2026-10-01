<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Attribute;
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
     * Return configurable attribute ids that, once all chosen in a listing, allow adding without the product page.
     *
     * @param Product $product
     * @return int[]
     */
    public function getQuickAddAttributeIds(Product $product): array
    {
        $type = $product->getTypeInstance();
        if (!$type instanceof Configurable || $type->hasRequiredOptions($product)) {
            return [];
        }

        $attributeIds = [];
        /** @var Attribute $attribute */
        foreach ($type->getConfigurableAttributes($product) as $attribute) {
            $attributeIds[] = (int) $attribute->getAttributeId();
        }

        return $attributeIds;
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
