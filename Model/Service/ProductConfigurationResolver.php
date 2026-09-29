<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductConfigurationResolver
{
    /**
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Return the concrete product selected by the buy request (child of a configurable) or the product itself.
     *
     * @param ProductInterface $product
     * @param array $buyRequest
     * @return ProductInterface
     */
    public function resolve(ProductInterface $product, array $buyRequest): ProductInterface
    {
        $superAttributes = $buyRequest['super_attribute'] ?? null;
        if (!$product instanceof Product
            || $product->getTypeId() !== Configurable::TYPE_CODE
            || !is_array($superAttributes)
            || $superAttributes === []
        ) {
            return $product;
        }

        $typeInstance = $product->getTypeInstance();
        if (!$typeInstance instanceof Configurable) {
            return $product;
        }

        $child = $typeInstance->getProductByAttributes($superAttributes, $product);
        if (!$child instanceof ProductInterface || !$child->getId()) {
            return $product;
        }

        try {
            return $this->productRepository->getById((int) $child->getId(), false, (int) $product->getStoreId());
        } catch (NoSuchEntityException) {
            return $product;
        }
    }
}
