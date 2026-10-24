<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostContributor extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'author_id', 'role', 'sort'];

    public const ROLES = [
        'writer' => 'نگارش',
        'translator' => 'ترجمه',
        'editor' => 'ویرایش',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
