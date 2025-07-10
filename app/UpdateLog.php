<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    protected $fillable = [
        'version', 'message','update_available'
    ];
}
