<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsentDocument extends Model
{
    protected $table = 'mst_consent_documents';

    protected $guarded = ['id'];

    protected $casts = [
        'effective_date' => 'date',
        'is_required' => 'bool',
        'is_active' => 'bool',
    ];

    public function getNameAttribute(): string
    {
        return $this->title;
    }

    public function consents(): HasMany
    {
        return $this->hasMany(Consent::class, 'consent_document_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
