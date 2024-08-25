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

    /** @var PrepareBodyHelper  */
    protected PrepareBodyHelper $bodyHelper;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->bodyHelper = new PrepareBodyHelper();
        $this->headers = ['x-api-key' => env('API_KEY')];
        $this->body = $this->bodyHelper->cities();
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
                ], 'result' => 175
            ],
            'Case 2' => [
                [
                    'Berlin' => ['Hamburg' => 40, 'Cologne' => 100, 'Frankfurt' => 120],
                    'Hamburg' => ['Berlin' => 40, 'Cologne' => 75, 'Frankfurt' => 90],
                    'Cologne' => ['Berlin' => 100, 'Hamburg' => 75, 'Frankfurt' => 50],
                    'Frankfurt' => ['Berlin' => 120, 'Hamburg' => 90, 'Cologne' => 50],
                ], 'result' => 165
            ],
            'Case 3' => [
                [
                    'Berlin' => ['Hamburg' => 200, 'Cologne' => 300, 'Frankfurt' => 400],
                    'Hamburg' => ['Berlin' => 200, 'Cologne' => 50, 'Frankfurt' => 50],
                    'Cologne' => ['Berlin' => 300, 'Hamburg' => 50, 'Frankfurt' => 120],
                    'Frankfurt' => ['Berlin' => 400, 'Hamburg' => 50, 'Cologne' => 120],
                ], 'result' => 350
            ],
        ];
    }

    /**
     * @param array $distances
     * @param float $result
     * @return void
     */
    #[DataProvider('providerForGoogleMock')]
    public function testOnSuccess(array $distances, float $result): void
    {
        $mockGoogleApi = Mockery::mock(CalculateTransportService::class)->makePartial();
        $mockGoogleApi->shouldReceive('callGoogleApi')
            ->andReturnUsing(function ($origin, $destination) use ($distances) {
                $originCity = $origin['city'];
                $destinationCity = $destination['city'];
                return $distances[$originCity][$destinationCity] ?? 0;
            });
        $this->app->instance(CalculateTransportService::class, $mockGoogleApi);
        $response = $this->sendRequest();
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
        return $this->withHeaders($this->headers)->postJson(env('APP_URL') . '/api/calculate-transport', $this->body);
    }
}
