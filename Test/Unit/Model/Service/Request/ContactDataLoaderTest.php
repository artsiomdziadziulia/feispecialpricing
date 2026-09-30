<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service\Request;

use Aheadworks\Ca\Api\CompanyRepositoryInterface;
use Aheadworks\Ca\Api\Data\CompanyInterface;
use Aheadworks\Ca\Api\Data\CompanySearchResultsInterface;
use Aheadworks\FeiSpecialPricing\Model\Request;
use Aheadworks\FeiSpecialPricing\Model\Service\Request\ContactDataLoader;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\TestCase;

class ContactDataLoaderTest extends TestCase
{
    /**
     * Customers and companies are loaded once for all requests and mapped by ID.
     *
     * @return void
     */
    public function testLoadMapsContactDataInBatch(): void
    {
        $criteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $criteriaBuilder->expects($this->exactly(2))->method('addFilter')
            ->willReturnCallback(function (string $field, array $ids) use ($criteriaBuilder) {
                static $expected = [['entity_id', [5, 6]], ['id', [3]]];
                $this->assertSame(array_shift($expected), [$field, array_values($ids)]);

                return $criteriaBuilder;
            });
        $criteriaBuilder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        $customerResults = $this->createMock(CustomerSearchResultsInterface::class);
        $customerResults->method('getItems')->willReturn([$this->createCustomer(5, 'John', 'Doe', 'john@x.com')]);
        $customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $customerRepository->expects($this->once())->method('getList')->willReturn($customerResults);

        $company = $this->createMock(CompanyInterface::class);
        $company->method('getId')->willReturn(3);
        $company->method('getName')->willReturn('Agency');
        $companyResults = $this->createMock(CompanySearchResultsInterface::class);
        $companyResults->method('getItems')->willReturn([$company]);
        $companyRepository = $this->createMock(CompanyRepositoryInterface::class);
        $companyRepository->expects($this->once())->method('getList')->willReturn($companyResults);

        $first = $this->createRequest(5, 3);
        $second = $this->createRequest(6, 3);
        (new ContactDataLoader($customerRepository, $companyRepository, $criteriaBuilder))->load([$first, $second]);

        $this->assertSame('John Doe', $first->getCustomerName());
        $this->assertSame('john@x.com', $first->getCustomerEmail());
        $this->assertSame('Agency', $first->getCompanyName());
        $this->assertNull($second->getCustomerName(), 'Deleted customer leaves contact data empty.');
        $this->assertSame('Agency', $second->getCompanyName());
    }

    /**
     * Empty list does not query repositories.
     *
     * @return void
     */
    public function testLoadEmptyDoesNothing(): void
    {
        $customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $customerRepository->expects($this->never())->method('getList');

        (new ContactDataLoader(
            $customerRepository,
            $this->createMock(CompanyRepositoryInterface::class),
            $this->createMock(SearchCriteriaBuilder::class)
        ))->load([]);
    }

    /**
     * Create request model without resource (data holder only).
     *
     * @param int $customerId
     * @param int $companyId
     * @return Request
     */
    private function createRequest(int $customerId, int $companyId): Request
    {
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        return $request->setCustomerId($customerId)->setCompanyId($companyId);
    }

    /**
     * Create customer stub.
     *
     * @param int $id
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @return CustomerInterface
     */
    private function createCustomer(int $id, string $firstname, string $lastname, string $email): CustomerInterface
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getId')->willReturn($id);
        $customer->method('getFirstname')->willReturn($firstname);
        $customer->method('getLastname')->willReturn($lastname);
        $customer->method('getEmail')->willReturn($email);

        return $customer;
    }
}
