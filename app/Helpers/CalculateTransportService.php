<?php

namespace App\Helpers;

use App\Models\VehicleType;
use GuzzleHttp\Client;

class CalculateTransportService
{
    public function callGoogleApi($origin, $destination)
    {
        $client = new Client();
        $response = $client->get(env('GOOGLE_API_URL'), [
            'query' => [
                'origin' => "{$origin['city']},{$origin['country']},{$origin['zip']}",
                'destination' => "{$destination['city']},{$destination['country']},{$destination['zip']}",
                'key' => env('GOOGLE_API_KEY'),
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        $distanceMeters = $data['routes'][0]['legs'][0]['distance']['value'];
        return $distanceMeters / 1000;
    }


    public function calculateDistance(array $cities)
    {
        $distance = 0;
        for ($i = 0; $i < count($cities)-1; $i++) {
            $distance += $this->callGoogleApi($cities[$i], $cities[$i+1]);
        }
        return $distance;
    }


    public function calculateByVehicle($distance)
    {
        return VehicleType::query()->get()->map(function ($type) use ($distance) {
            $cost = $distance * $type->cost_km;
            $cost = max($cost, $type->minimum);
            return ['vehicle_type' => $type->number, 'price' => $cost];
        });
    }

}
