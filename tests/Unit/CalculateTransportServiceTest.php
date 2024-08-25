<?php

namespace Tests\Unit;

use App\Helpers\CalculateTransportService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Helpers\PrepareBodyHelper;
use Tests\TestCase;

class CalculateTransportServiceTest extends TestCase
{

    /** @var array */
    private array $cities;

    /** @var MockInterface */
    protected MockInterface $service;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cities = (new PrepareBodyHelper())->cities();
        $this->service = Mockery::mock(CalculateTransportService::class)->makePartial();

    }

    /**
     * @return array[]
     */
    public static function providerForGoogleMock(): array
    {
        return [
            'Case 1' => [
                [
                    'Berlin' => ['Hamburg' => 50, 'Cologne' => 100, 'Frankfurt' => 150],
                    'Hamburg' => ['Berlin' => 50, 'Cologne' => 75, 'Frankfurt' => 90],
                    'Cologne' => ['Berlin' => 100, 'Hamburg' => 75, 'Frankfurt' => 50],
                    'Frankfurt' => ['Berlin' => 150, 'Hamburg' => 90, 'Cologne' => 50],
                ]
            ],
            'Case 2' => [
                [
                    'Berlin' => ['Hamburg' => 40, 'Cologne' => 100, 'Frankfurt' => 120],
                    'Hamburg' => ['Berlin' => 40, 'Cologne' => 75, 'Frankfurt' => 90],
                    'Cologne' => ['Berlin' => 100, 'Hamburg' => 75, 'Frankfurt' => 50],
                    'Frankfurt' => ['Berlin' => 120, 'Hamburg' => 90, 'Cologne' => 50],
                ]
            ],
            'Case 3' => [
                [
                    'Berlin' => ['Hamburg' => 200, 'Cologne' => 300, 'Frankfurt' => 400],
                    'Hamburg' => ['Berlin' => 200, 'Cologne' => 50, 'Frankfurt' => 50],
                    'Cologne' => ['Berlin' => 300, 'Hamburg' => 50, 'Frankfurt' => 120],
                    'Frankfurt' => ['Berlin' => 400, 'Hamburg' => 50, 'Cologne' => 120],
                ]
            ],
        ];
    }

    /**
     * @param array $distances
     * @return void
     */
    #[DataProvider('providerForGoogleMock')]
    public function testBuildMaxtricForGraphMethod(array $distances): void
    {
        $this->service->shouldReceive('callGoogleApi')
            ->andReturnUsing(function ($origin, $destination) use ($distances) {
                $originCity = $origin['city'];
                $destinationCity = $destination['city'];
                return $distances[$originCity][$destinationCity] ?? 0;
            });
        $this->app->instance(CalculateTransportService::class, $this->service);

        $grid = $this->service->buildMaxtricForGraph($this->cities['addresses']);
        $cities = array_keys($distances);
        foreach ($cities as $i => $cityA) {
            foreach ($cities as $j => $cityB) {
                if ($i !== $j) { // Avoid self-comparison
                    $expectedDistance = $distances[$cityA][$cityB];
                    $this->assertEquals(
                        $expectedDistance,
                        $grid[$i][$j],
                        "Distance between $cityA and $cityB should be $expectedDistance"
                    );
                }
            }
        }
    }


    /**
     * @return array
     */
    public static function providerForDijkstra(): array
    {
        return [
            'Case 1' => [
                [
                    [0, 283.885, 576.344, 547.528],
                    [283.885, 0, 425.572, 492.125],
                    [576.344, 425.572, 0, 192.593],
                    [547.528, 492.125, 192.593, 0]
                ], 'result' => 902.05
            ],
            'Case 2' => [
                [
                    [0, 200, 300, 400],
                    [200, 0, 50, 50],
                    [300, 50, 0, 120],
                    [400, 50, 120, 0]
                ], 'result' => 350
            ]
        ];
    }

    /**
     * @param array $grid
     * @param float $result
     * @return void
     */
    #[DataProvider('providerForDijkstra')]
    public function testDijkstraMinCostAllNodes(array $grid, float $result): void
    {
        $response = $this->service->dijkstraMinCostAllNodes($grid);
        $this->assertEquals($result, $response);
    }

}
