<?php

namespace App\Models;

use App\Enums\FileStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'path'];

    protected $casts  = [FileStatusEnum::class];
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
