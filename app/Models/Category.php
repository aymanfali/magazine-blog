<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasUuids;
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Primary Key
    |--------------------------------------------------------------------------
    */

    public $incrementing = false;

    protected $keyType = 'string';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'parent_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'image_url',
        'parent_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Storage::disk('public')->url(
            $this->image
        );
    }

    public function getParentNameAttribute(): string
    {
        return $this->parent?->name ?? __('frontend.app.main_category');
    }
}
