<?php

namespace Tests\Unit;

use Models\Items;
use PHPUnit\Framework\TestCase;
use Services\Pantry\PantryIntake;
use Services\Pantry\Sources\CatalogSource;

/** In-memory Items stand-in: records create() payloads, skips the DB. */
class FakeItems extends Items
{
    public array $created = [];

    public function __construct()
    {
        // no DB
    }

    public function create(array $data): int
    {
        $this->created[] = $data;
        return count($this->created);
    }
}

/** Scriptable CatalogSource. */
class FakeSource implements CatalogSource
{
    public function __construct(
        private string $kind = 'ingredient',
        public ?int $exactId = null,
        public array $choices = [],
        public ?int $apiResult = null,
        public bool $manualSupported = true,
        public ?int $manualResult = 42,
    ) {}

    public function kind(): string { return $this->kind; }
    public function findExactId(string $name, ?string $brand): ?int { return $this->exactId; }
    public function searchChoices(string $name, ?string $brand): array { return $this->choices; }
    public function ensureFromApi(int|string $apiId, string $name, ?string $brand): ?int { return $this->apiResult; }
    public function supportsManual(): bool { return $this->manualSupported; }
    public function createManual(string $name, ?string $brand): ?int { return $this->manualResult; }

    public function itemColumns(int $catalogId): array
    {
        return $this->kind === 'product'
            ? ['ingredient_id' => null, 'product_id' => $catalogId]
            : ['ingredient_id' => $catalogId, 'product_id' => null];
    }
}

class PantryIntakeTest extends TestCase
{
    private FakeItems $items;

    protected function setUp(): void
    {
        $this->items = new FakeItems();
    }

    private function intake(FakeSource $source): PantryIntake
    {
        return new PantryIntake($source, $this->items);
    }

    // ---------- begin ----------

    public function testBeginSavesImmediatelyOnExactMatch()
    {
        $source = new FakeSource(exactId: 7);
        $result = $this->intake($source)->begin(
            ['name' => 'Oats', 'brand' => 'Quaker', 'quantity' => 2, 'unit' => 'cup'],
            99
        );

        $this->assertSame('saved', $result['type']);
        $this->assertCount(1, $this->items->created);
        $row = $this->items->created[0];
        $this->assertSame(99, $row['user_id']);
        $this->assertSame(7, $row['ingredient_id']);
        $this->assertNull($row['product_id']);
        $this->assertSame('Oats', $row['entered_name']);
        $this->assertSame('Quaker', $row['entered_brand']);
    }

    public function testBeginReturnsChoicesWhenNoExactMatch()
    {
        $source = new FakeSource(exactId: null, choices: [['name' => 'Choice A']]);
        $result = $this->intake($source)->begin(['name' => 'Oats'], 99);

        $this->assertSame('confirm', $result['type']);
        $this->assertSame([['name' => 'Choice A']], $result['choices']);
        $this->assertCount(0, $this->items->created);
    }

    // ---------- complete: local pick ----------

    public function testCompleteLocalPickSaves()
    {
        $source = new FakeSource();
        $result = $this->intake($source)->complete([
            'picked_source'  => 'local',
            'ingredient_id'  => 5,
            'original_input' => ['name' => 'Rice', 'quantity' => 1],
        ], 99);

        $this->assertSame('saved', $result['type']);
        $this->assertSame(5, $this->items->created[0]['ingredient_id']);
    }

    public function testCompleteLocalPickUsesProductKeyForProducts()
    {
        $source = new FakeSource(kind: 'product');
        $result = $this->intake($source)->complete([
            'picked_source'  => 'local',
            'product_id'     => 8,
            'original_input' => ['name' => 'Crackers'],
        ], 99);

        $this->assertSame('saved', $result['type']);
        $this->assertSame(8, $this->items->created[0]['product_id']);
        $this->assertNull($this->items->created[0]['ingredient_id']);
    }

    // ---------- complete: manual ----------

    public function testCompleteManualCreatesCatalogRowAndSaves()
    {
        $source = new FakeSource(manualResult: 42);
        $result = $this->intake($source)->complete([
            'picked_source'  => 'manual',
            'original_input' => ['name' => 'Homemade jam'],
        ], 99);

        $this->assertSame('saved', $result['type']);
        $this->assertSame(42, $this->items->created[0]['ingredient_id']);
    }

    public function testCompleteMissingApiIdFallsBackToManualForIngredients()
    {
        // Legacy ingredient behavior: empty api_id implies manual save.
        $source = new FakeSource(manualResult: 42);
        $result = $this->intake($source)->complete([
            'api_id'         => '',
            'original_input' => ['name' => 'Mystery spice'],
        ], 99);

        $this->assertSame('saved', $result['type']);
    }

    public function testCompleteManualUnsupportedForProducts()
    {
        $source = new FakeSource(kind: 'product', manualSupported: false, manualResult: null);
        $result = $this->intake($source)->complete([
            'picked_source'  => 'manual',
            'original_input' => ['name' => 'Crackers'],
        ], 99);

        $this->assertSame('manual_unsupported', $result['type']);
        $this->assertCount(0, $this->items->created);
    }

    public function testCompleteMissingApiIdIsErrorForProducts()
    {
        $source = new FakeSource(kind: 'product', manualSupported: false, manualResult: null);
        $result = $this->intake($source)->complete([
            'api_id'         => '',
            'original_input' => ['name' => 'Crackers'],
        ], 99);

        $this->assertSame('error', $result['type']);
        $this->assertSame('Please select an option.', $result['message']);
    }

    public function testCompleteManualPersistenceFailureIsError()
    {
        $source = new FakeSource(manualResult: null);
        $result = $this->intake($source)->complete([
            'picked_source'  => 'manual',
            'original_input' => ['name' => 'Jam'],
        ], 99);

        $this->assertSame('error', $result['type']);
        $this->assertCount(0, $this->items->created);
    }

    // ---------- complete: provider ----------

    public function testCompleteProviderPathSaves()
    {
        $source = new FakeSource(apiResult: 33);
        $result = $this->intake($source)->complete([
            'api_id'         => '1750341',
            'original_input' => ['name' => 'Gala Apple', 'quantity' => 3],
        ], 99);

        $this->assertSame('saved', $result['type']);
        $this->assertSame(33, $this->items->created[0]['ingredient_id']);
        $this->assertSame(3, $this->items->created[0]['quantity']);
    }

    public function testCompleteProviderFailureIsError()
    {
        $source = new FakeSource(apiResult: null);
        $result = $this->intake($source)->complete([
            'api_id'         => '1750341',
            'original_input' => ['name' => 'Gala Apple'],
        ], 99);

        $this->assertSame('error', $result['type']);
        $this->assertStringContainsString('provider', $result['message']);
    }

    public function testCompleteDefaultsQuantityToOne()
    {
        $source = new FakeSource(apiResult: 33);
        $this->intake($source)->complete([
            'api_id'         => 'x1',
            'original_input' => ['name' => 'Apple'],
        ], 99);

        $this->assertSame(1, $this->items->created[0]['quantity']);
    }
}
