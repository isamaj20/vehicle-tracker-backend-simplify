<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['device_id', 'device_name','user_id', 'sim_number', 'ignition'];

    public function locations()
    {
        return $this->hasMany(Location::class, 'device_id', 'device_id');
    }

    public function commands()
    {
        return $this->hasMany(DeviceCommand::class, 'device_id', 'device_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function category()
    {
        return $this->belongsTo(DeviceCategory::class, 'device_category_id');
    }
}
