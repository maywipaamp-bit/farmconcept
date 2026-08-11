<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorExpertise extends Model
{
    protected $table = 'mst_instructor_expertises';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
