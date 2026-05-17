<?php

namespace App\Models;

use Database\Factories\KpiTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplate extends Model
{
    /** @use HasFactory<KpiTemplateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'year',
        'division_id',
        'department_id',
        'position_id',
        'revision',
        'description',
        'is_active',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * @return HasMany<KpiTemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(KpiTemplateItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<KpiAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(KpiAssignment::class);
    }
}
