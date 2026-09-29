<?php
declare(strict_types=1);

namespace Aheadworks\FeiSpecialPricing\Test\Unit\Model\Service\Basket;

use Aheadworks\FeiSpecialPricing\Model\Service\Basket\BuyRequestNormalizer;
use PHPUnit\Framework\TestCase;

class BuyRequestNormalizerTest extends TestCase
{
    /**
     * @var BuyRequestNormalizer
     */
    private BuyRequestNormalizer $normalizer;

    /**
     * Set up test subject.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->normalizer = new BuyRequestNormalizer();
    }

    /**
     * Transient keys and empty values are removed, options are sorted.
     *
     * @return void
     */
    public function testNormalizeStripsTransientKeys(): void
    {
        $result = $this->normalizer->normalize([
            'form_key' => 'abc',
            'qty' => 3,
            'product' => 10,
            'uenc' => 'x',
            'super_attribute' => [93 => 5],
            'options' => [],
            'bundle_option' => [1 => 2],
        ]);

        $this->assertSame(['bundle_option' => [1 => 2], 'super_attribute' => [93 => 5]], $result);
    }

    /**
     * Same options with different transient data are treated as identical.
     *
     * @return void
     */
    public function testIsSameIgnoresTransientData(): void
    {
        $this->assertTrue($this->normalizer->isSame(
            ['super_attribute' => [93 => 5], 'qty' => 1],
            ['form_key' => 'k', 'super_attribute' => [93 => 5]]
        ));
        $this->assertFalse($this->normalizer->isSame(
            ['super_attribute' => [93 => 5]],
            ['super_attribute' => [93 => 6]]
        ));
    }
}
