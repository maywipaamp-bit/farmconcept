<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = 'mst_districts';

    public $timestamps = false;

    protected $guarded = ['id'];
}
