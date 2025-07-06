<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'denominations' => 'array',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the Cash registers transactions.
     */
    public function cash_register_transactions()
    {
        return $this->hasMany(\App\CashRegisterTransaction::class);
    }
	/**
     * Get the device associated with the cash register.
     */
    public function device()
    {
        return $this->belongsTo(\App\Device::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
