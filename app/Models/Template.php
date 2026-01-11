<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = [
        'code',
        'name',
        'version',
    ];

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }
}
