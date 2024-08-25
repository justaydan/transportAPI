<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $collection = 'cities';
}
