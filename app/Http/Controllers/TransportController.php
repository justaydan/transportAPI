<?php

namespace App\Http\Controllers;

use App\Helpers\CalculateTransportService;
use App\Http\Requests\TransportCalculationRequest;
use Symfony\Component\HttpFoundation\Response;
use function Laravel\Prompts\error;

class TransportController extends Controller
{
    public function __construct(private CalculateTransportService $calculateTransportHelper)
    {
    }


    public function calculate(TransportCalculationRequest $request)
    {
        try {
            $distances = $this->calculateTransportHelper->buildMaxtricForGraph($request->addresses);
            $shortestPath = $this->calculateTransportHelper->dijkstraMinCostAllNodes($distances);
            return $this->calculateTransportHelper->calculateByVehicle($shortestPath);
        } catch (\Exception $exception) {
            error($exception->getMessage());
            return response()->json('Something went wrong', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


}
