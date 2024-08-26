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

    /** @var MockInterface */
    protected MockInterface $service;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Mockery::mock(CalculateTransportService::class)->makePartial();
    }

    /**
     * @return array[]
     */
    public static function providerForGoogleMock(): array
    {
        return [
            [
                "distances" => [
                    'Berlin' => ['Hamburg' => 50],
                    'Hamburg' => ['Cologne' => 75],
                    'Cologne' => ['Frankfurt' => 50],
                ],
                'cities' => [
                    [
                        "country" => "DE",
                        "zip" => "10115",
                        "city" => "Berlin"
                    ],
                    [
                        "country" => "DE",
                        "zip" => "20095",
                        "city" => "Hamburg"
                    ],
                    [
                        "city" => "Cologne",
                        "zip" => "50667",
                        "country" => "DE"
                    ]
                    , [
                        "city" => "Frankfurt",
                        "zip" => "60311",
                        "country" => "DE"
                    ]
                ], 'result' => 175
            ],
            [
                "distances" => [
                    'Berlin' => ['Cologne' => 80],
                    'Cologne' => ['Frankfurt' => 150],
                    'Frankfurt' => ['Berlin' => 100],
                ],
                'cities' => [
                    [
                        "country" => "DE",
                        "zip" => "10115",
                        "city" => "Berlin"
                    ],
                    [
                        "city" => "Cologne",
                        "zip" => "50667",
                        "country" => "DE"
                    ],
                    [
                        "city" => "Frankfurt",
                        "zip" => "60311",
                        "country" => "DE"
                    ], [
                        "country" => "DE",
                        "zip" => "20095",
                        "city" => "Berlin"
                    ]
                ], 'result' => 330
            ],
            [
                "distances" => [
                    'Berlin' => ['Frankfurt' => 123],
                    'Frankfurt' => ['Cologne' => 56],
                    'Cologne' => ['Hamburg' => 50],
                ],
                'cities' => [
                    [
                        "country" => "DE",
                        "zip" => "10115",
                        "city" => "Berlin"
                    ],
                    [
                        "city" => "Frankfurt",
                        "zip" => "60311",
                        "country" => "DE"
                    ],
                    [
                        "city" => "Cologne",
                        "zip" => "50667",
                        "country" => "DE"
                    ],
                    [
                        "country" => "DE",
                        "zip" => "20095",
                        "city" => "Hamburg"
                    ],

                ], 'result' => 229
            ],
            [
                "distances" => [
                    'Berlin' => ['Frankfurt' => 23],
                ],
                'cities' => [
                    [
                        "country" => "DE",
                        "zip" => "10115",
                        "city" => "Berlin"
                    ],
                    [
                        "city" => "Frankfurt",
                        "zip" => "60311",
                        "country" => "DE"
                    ]
                ], 'result' => 23
            ]
        ];
    }

    /**
     * @param array $distances
     * @param array $cities
     * @param int $result
     * @return void
     */
    #[DataProvider('providerForGoogleMock')]
    public function testCalculateDistanceMethod(array $distances, array $cities, int $result): void
    {
        $this->service->shouldReceive('callGoogleApi')
            ->andReturnUsing(function ($origin, $destination) use ($distances) {
                $originCity = $origin['city'];
                $destinationCity = $destination['city'];
                return $distances[$originCity][$destinationCity] ?? 0;
            });
        $this->app->instance(CalculateTransportService::class, $this->service);

        $distance = $this->service->calculateDistance($cities);
        $this->assertEquals($result, $distance);
    }



}
