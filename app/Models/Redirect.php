<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = ['from_path', 'to_url', 'status_code', 'enabled'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'status_code' => 'integer',
        ];
    }
}
