<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewComment extends Model
{
    protected $table = 'rev_review_comments';

    protected $guarded = ['id'];

    protected $casts = ['is_resolved' => 'boolean'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ReviewItem::class, 'item_id');
    }
}
