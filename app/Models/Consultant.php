<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'password', 'api_token', 'area_name',
    ];

    protected $hidden = [
        'password', 'api_token',
    ];

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'assigned_to_id');
    }
}
