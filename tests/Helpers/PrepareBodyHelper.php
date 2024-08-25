<?php

namespace Tests\Helpers;

class PrepareBodyHelper
{

    /**
     * @return array[]
     */
    public function cities(): array
    {
        return [
            "addresses" => [
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
            ]
        ];
    }

}
