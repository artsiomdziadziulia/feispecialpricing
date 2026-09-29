<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;

class RequestOrderLinker
{
    /**
     * @param RequestRepositoryInterface $requestRepository
     */
    public function __construct(
        private readonly RequestRepositoryInterface $requestRepository
    ) {
    }

    /**
     * Mark requests whose approved prices were used in the order as "ordered".
     *
     * @param OrderInterface $order
     * @return int[] IDs of updated requests
     * @throws CouldNotSaveException
     */
    public function markOrdered(OrderInterface $order): array
    {
        $requestIds = [];
        foreach ($order->getItems() as $orderItem) {
            if (!$orderItem instanceof DataObject) {
                continue;
            }
            $linkId = (int) $orderItem->getData(RequestToCartService::ITEM_LINK_FIELD);
            if ($linkId === 0) {
                continue;
            }
            try {
                $requestIds[] = $this->requestRepository->getItemById($linkId)->getRequestId();
            } catch (NoSuchEntityException) {
                continue;
            }
        }

        $updated = [];
        foreach (array_unique($requestIds) as $requestId) {
            try {
                $request = $this->requestRepository->getById($requestId);
            } catch (NoSuchEntityException) {
                continue;
            }
            if ($request->getStatus() !== Status::Approved->value) {
                continue;
            }
            $request->setStatus(Status::Ordered->value)
                ->setOrderId((int) $order->getEntityId());
            $this->requestRepository->save($request);
            $updated[] = $requestId;
        }

        return $updated;
    }
}
