<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class RequestActions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Add "Review" link to each row.
     *
     * @param array $dataSource
     * @return array
     */
    #[\Override]
    public function prepareDataSource(array $dataSource): array
    {
        foreach ($dataSource['data']['items'] ?? [] as $index => $item) {
            if (!isset($item['id'])) {
                continue;
            }
            $dataSource['data']['items'][$index][$this->getData('name')] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl('aw_fei_sp_admin/request/edit', ['id' => $item['id']]),
                    'label' => __('Review'),
                ],
            ];
        }

        return $dataSource;
    }
}
