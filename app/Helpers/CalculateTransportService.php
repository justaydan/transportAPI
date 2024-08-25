<?php

namespace App\Helpers;

use App\Models\VehicleType;
use GuzzleHttp\Client;
use SplPriorityQueue;

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


    public function buildMaxtricForGraph(array $cities)
    {
        $grid = array_fill(0, count($cities), array_fill(0, count($cities), 0)); // Pre-fill the grid with zeros
        for ($i = 0; $i < count($cities); $i++) {
            for ($j = $i + 1; $j < count($cities); $j++) {
                $km = $this->callGoogleApi($cities[$i], $cities[$j]);
                $grid[$i][$j] = $grid[$j][$i] = $km;
            }
        }
        return $grid;
    }


    public function dijkstraMinCostAllNodes($graph)
    {
        $n = count($graph);

        $pq = new SplPriorityQueue();
        $pq->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

        $dist = [];

        for ($i = 0; $i < $n; $i++) {
            $visited_set = [$i];
            $visited_key = implode(',', $visited_set);
            $pq->insert([$visited_set, $i], 0);
            $dist[$visited_key][$i] = 0;
        }

        while (!$pq->isEmpty()) {
            $element = $pq->extract();
            $cost = -$element['priority'];
            list($visited_set, $u) = $element['data'];

            if (count($visited_set) == $n) {
                return $cost;
            }

            for ($v = 0; $v < $n; $v++) {
                if ($graph[$u][$v] > 0) {  // There is an edge between $u and $v
                    $new_visited_set = array_unique(array_merge($visited_set, [$v]));
                    sort($new_visited_set); // Ensure the array is sorted to maintain consistent keys
                    $new_visited_key = implode(',', $new_visited_set);
                    $new_cost = $cost + $graph[$u][$v];

                    if (!isset($dist[$new_visited_key][$v]) || $new_cost < $dist[$new_visited_key][$v]) {
                        $dist[$new_visited_key][$v] = $new_cost;
                        $pq->insert([$new_visited_set, $v], -$new_cost); // negative cost for min-heap
                    }
                }
            }
        }

        return -1;
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
