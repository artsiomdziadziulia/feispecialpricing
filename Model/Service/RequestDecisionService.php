<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Model\Service;

use Aheadworks\FeiSpecialPricing\Api\Data\RequestInterface;
use Aheadworks\FeiSpecialPricing\Api\RequestRepositoryInterface;
use Aheadworks\FeiSpecialPricing\Model\Config;
use Aheadworks\FeiSpecialPricing\Model\Request\Status;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class RequestDecisionService
{
    public const string EVENT_DECIDED = 'aw_fei_sp_request_decided';

    private const array DECIDABLE_STATUSES = [Status::Pending, Status::Approved];

    /**
     * @param RequestRepositoryInterface $requestRepository
     * @param Config $config
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     */
    public function __construct(
        private readonly RequestRepositoryInterface $requestRepository,
        private readonly Config $config,
        private readonly DateTime $dateTime,
        private readonly EventManager $eventManager
    ) {
    }

    /**
     * Approve request with special prices per item.
     *
     * @param int $requestId
     * @param array $specialPrices Request item ID => special price
     * @param string|null $expiresAt UTC datetime; default validity period applies when null
     * @param string|null $adminComment
     * @return RequestInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function approve(
        int $requestId,
        array $specialPrices,
        ?string $expiresAt = null,
        ?string $adminComment = null
    ): RequestInterface {
        $request = $this->requestRepository->getById($requestId);
        $this->assertDecidable($request);

        foreach ($request->getItems() as $item) {
            $price = $specialPrices[(int) $item->getId()] ?? null;
            if ($price === null || $price === '' || !is_numeric($price) || (float) $price < 0) {
                throw new LocalizedException(
                    __('Please specify a valid special price for "%1".', $item->getName())
                );
            }
            $item->setSpecialPrice(round((float) $price, 4));
        }

        $expiresAt = $expiresAt ?: $this->dateTime->gmtDate(
            'Y-m-d H:i:s',
            $this->dateTime->gmtTimestamp() + $this->config->getDefaultExpirationDays($request->getStoreId()) * 86400
        );
        if (strtotime($expiresAt . ' UTC') <= $this->dateTime->gmtTimestamp()) {
            throw new LocalizedException(__('The expiration date must be in the future.'));
        }

        $request->setStatus(Status::Approved->value)
            ->setExpiresAt($expiresAt)
            ->setAdminComment($this->normalizeComment($adminComment));

        return $this->saveAndNotify($request);
    }

    /**
     * Reject request.
     *
     * @param int $requestId
     * @param string|null $adminComment
     * @return RequestInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws CouldNotSaveException
     */
    public function reject(int $requestId, ?string $adminComment = null): RequestInterface
    {
        $request = $this->requestRepository->getById($requestId);
        $this->assertDecidable($request);

        $request->setStatus(Status::Rejected->value)
            ->setAdminComment($this->normalizeComment($adminComment));

        return $this->saveAndNotify($request);
    }

    /**
     * Ensure the request is still open for a decision.
     *
     * @param RequestInterface $request
     * @return void
     * @throws LocalizedException
     */
    private function assertDecidable(RequestInterface $request): void
    {
        $status = Status::tryFrom($request->getStatus());
        if (!in_array($status, self::DECIDABLE_STATUSES, true)) {
            throw new LocalizedException(
                __('Request #%1 is %2 and can no longer be changed.', $request->getId(), $status?->label() ?? '')
            );
        }
    }

    /**
     * Persist the decision and dispatch the notification event.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     * @throws CouldNotSaveException
     */
    private function saveAndNotify(RequestInterface $request): RequestInterface
    {
        $this->requestRepository->save($request);
        $this->eventManager->dispatch(self::EVENT_DECIDED, ['request' => $request]);

        return $request;
    }

    /**
     * Trim comment and convert empty value to null.
     *
     * @param string|null $comment
     * @return string|null
     */
    private function normalizeComment(?string $comment): ?string
    {
        $comment = $comment !== null ? trim($comment) : null;

        return $comment !== '' ? $comment : null;
    }
}
