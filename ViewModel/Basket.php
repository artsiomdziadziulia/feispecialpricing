<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\ViewModel;

use Aheadworks\FeiSpecialPricing\Model\Service\BasketService;
use Aheadworks\FeiSpecialPricing\Model\Service\CurrentCompanyUserProvider;
use Aheadworks\FeiSpecialPricing\Model\Service\ProductConfigurationResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Basket implements ArgumentInterface
{
    /**
     * @var array<int, array{id: int, name: string, sku: string, url: string, qty: float, price: float|null}>|null
     */
    private ?array $rows = null;

    /**
     * @param BasketService $basketService
     * @param CurrentCompanyUserProvider $companyUserProvider
     * @param ProductRepositoryInterface $productRepository
     * @param ProductConfigurationResolver $configurationResolver
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private readonly BasketService $basketService,
        private readonly CurrentCompanyUserProvider $companyUserProvider,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductConfigurationResolver $configurationResolver,
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * Return basket rows prepared for rendering.
     *
     * @return array<int, array{id: int, name: string, sku: string, url: string, qty: float, price: float|null}>
     */
    public function getRows(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        $this->rows = [];
        $customerId = $this->companyUserProvider->getCustomerId();
        if ($customerId === null) {
            return $this->rows;
        }

        foreach ($this->basketService->getItems($customerId) as $item) {
            try {
                $product = $this->productRepository->getById($item->getProductId());
            } catch (NoSuchEntityException) {
                continue;
            }
            $concrete = $this->configurationResolver->resolve($product, $item->getBuyRequest());

            $this->rows[] = [
                'id' => (int) $item->getId(),
                'name' => (string) $concrete->getName(),
                'sku' => (string) $concrete->getSku(),
                'url' => $product instanceof Product ? (string) $product->getProductUrl() : '',
                'qty' => $item->getQty(),
                'price' => $concrete instanceof Product
                    ? (float) $concrete->getPriceInfo()->getPrice('final_price')->getAmount()->getValue()
                    : null,
            ];
        }

        return $this->rows;
    }

    /**
     * Return update/remove form action URL.
     *
     * @return string
     */
    public function getUpdateUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/basket/updatePost');
    }

    /**
     * Return submit form action URL.
     *
     * @return string
     */
    public function getSubmitUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/basket/submit');
    }

    /**
     * Return requests list URL.
     *
     * @return string
     */
    public function getRequestsUrl(): string
    {
        return $this->urlBuilder->getUrl('aw_fei_sp/request/index');
    }
}
