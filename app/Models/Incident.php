<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni_type', 'dni_number', 'full_name', 'area_name', 'corporate_email',
        'category', 'apps', 'description', 'urgency',
        'hostname', 'os', 'office_version', 'first_time', 'started_at',
        'status', 'assigned_to_id', 'consultant_notes', 'resolution_date', 'solution_applied',
    ];

    protected $casts = [
        'apps' => 'array',
        'first_time' => 'boolean',
        'started_at' => 'date',
        'resolution_date' => 'datetime',
    ];

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Consultant::class, 'assigned_to_id');
    }
}
