<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceCategory extends Model
{
    use HasFactory, HasUuids;

    //public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }
}
