<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    protected $table = 'devices';

    protected $fillable = [
        'device_serial_number',
        'device_make',
        'device_friendly_name',
        'device_id',
        'status',
        'cardknox_api_key',
        'user_id',
        'business_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope a query to only include devices for the current business.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBusiness($query)
    {
        $business_id = request()->session()->get('user.business_id');
        return $query->where('business_id', $business_id);
    }

        public function cashRegisters()
    {
        return $this->hasMany(CashRegister::class);
    }

    // Relation only with status = 'open'
    // public function openCashRegisters()
    // {
    //     return $this->hasMany(CashRegister::class);
    // }

    public function openCashRegisters()
    {
        return $this->hasMany(CashRegister::class)
                    ->where('status', 'open');
    }
}
