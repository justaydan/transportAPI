<?php

namespace Tests\Feature;


use App\Helpers\CalculateTransportService;
use App\Models\VehicleType;
use Illuminate\Testing\TestResponse;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Helpers\PrepareBodyHelper;
use Tests\TestCase;

class CalculateTransportPriceTest extends TestCase
{
    /** @var array */
    protected array $headers;

    /** @var array */
    protected array $body;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->headers = ['x-api-key' => env('API_KEY')];
        $this->body = [];
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
                ], 'result' => 175, 'status' => Response::HTTP_OK
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
                        "zip" => "10115",
                        "city" => "Berlin"
                    ]
                ], 'result' => 330, 'status' => Response::HTTP_OK
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

                ], 'result' => 229, 'status' => Response::HTTP_OK
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
                ], 'result' => 23, 'status' => Response::HTTP_OK
            ]
        ];
    }

    /**
     * @param array $distances
     * @param array $cities
     * @param float $result
     * @param int $status
     * @return void
     */
    #[DataProvider('providerForGoogleMock')]
    public function testOnSuccess(array $distances, array $cities, float $result, int $status): void
    {
        $mockGoogleApi = Mockery::mock(CalculateTransportService::class)->makePartial();
        $mockGoogleApi->shouldReceive('callGoogleApi')
            ->andReturnUsing(function ($origin, $destination) use ($distances) {
                $originCity = $origin['city'];
                $destinationCity = $destination['city'];
                return $distances[$originCity][$destinationCity] ?? 0;
            });
        $this->app->instance(CalculateTransportService::class, $mockGoogleApi);
        $this->body['addresses'] = $cities;
        $response = $this->sendRequest();
        $response->assertStatus($status);
        $vehiclePrices = $this->calculatePrice($result);
        $response->assertStatus(Response::HTTP_OK);
        $this->assertEqualsCanonicalizing($vehiclePrices, $response->json());
    }


    /**
     * @return void
     */
    public function testOnWithoutHeader(): void
    {
        $this->headers = [];
        $this->sendRequest()->assertUnauthorized();
    }

    /**
     * @return array[]
     */
    public static function providerForDifferentCases(): array
    {
        return [
            [
                [],
                "status" => Response::HTTP_UNPROCESSABLE_ENTITY
            ],
            [
                [
                    [
                        "country" => "DE",
                        "zip" => "10115",
                        "city" => "Berlin"
                    ],
                    [
                        "country" => "DE",
                        "zip" => "200e95",
                        "city" => "Hamburg"
                    ]
                ],
                "status" => Response::HTTP_UNPROCESSABLE_ENTITY

            ],
            [
                [
                    [
                        "country" => "DE",
                        "zip" => "10115",
                        "city" => "Berlin"
                    ]
                ],
                "status" => Response::HTTP_UNPROCESSABLE_ENTITY

            ]
        ];
    }

    /**
     * @param array $cities
     * @param int $status
     * @return void
     */
    #[DataProvider('providerForDifferentCases')]
    public function testUnprocessableData(array $cities, int $status): void
    {
        $this->body = $cities;
        $response = $this->sendRequest();
        $response->assertStatus($status);
    }

    /**
     * @param float $distance
     * @return array
     */
    private function calculatePrice(float $distance): array
    {
        return VehicleType::query()->get()->map(function ($type) use ($distance) {
            $cost = $distance * $type->cost_km;
            $cost = max($cost, $type->minimum);
            return ['vehicle_type' => $type->number, 'price' => $cost];
        })->toArray();
    }

    /**
     * @return TestResponse
     */
    public function sendRequest(): TestResponse
    {
        return $this->withHeaders($this->headers)->getJson(env('APP_URL') . '/api/calculate-transport' . '?' . http_build_query($this->body));
    }
}
