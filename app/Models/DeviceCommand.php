<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceCommand extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['device_id', 'command', 'executed'];

    public function device()
    {
        return $this->hasMany(DeviceCommand::class, 'device_id', 'device_id');
    }
}
