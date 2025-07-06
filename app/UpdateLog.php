<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    protected $fillable = [
        'status', 'message', 'updated_by', 'update_available'
    ];
}
