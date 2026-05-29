<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TrackingLog extends Model {
    protected $fillable = ['container_id', 'location', 'description'];
}
