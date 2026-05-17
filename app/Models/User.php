<?php

namespace App\Models;

use App\Enums\SystemPermission;
use App\Enums\SystemRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_code',
        'whatsapp',
        'phone',
        'division_id',
        'department_id',
        'position_id',
        'supervisor_id',
        'employment_status',
        'joined_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joined_at' => 'date',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can(SystemPermission::ACCESS_ADMIN_PANEL->value);
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
     * @return BelongsTo<User, $this>
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supervisor_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(self::class, 'supervisor_id');
    }

    /**
     * @return HasMany<KpiAssignment, $this>
     */
    public function kpiAssignments(): HasMany
    {
        return $this->hasMany(KpiAssignment::class, 'employee_id');
    }

    /**
     * @return HasMany<KpiAssignment, $this>
     */
    public function assignedKpiAssignments(): HasMany
    {
        return $this->hasMany(KpiAssignment::class, 'assigned_by');
    }

    /**
     * @return HasMany<KpiAssessment, $this>
     */
    public function kpiAssessments(): HasMany
    {
        return $this->hasMany(KpiAssessment::class, 'employee_id');
    }

    /**
     * @return HasMany<KpiAssessment, $this>
     */
    public function assessedKpiAssessments(): HasMany
    {
        return $this->hasMany(KpiAssessment::class, 'assessor_id');
    }

    public function manages(User $user): bool
    {
        return $user->supervisor_id === $this->getKey();
    }

    public function isManager(): bool
    {
        return $this->hasRole(SystemRole::MANAGER->value);
    }
}
