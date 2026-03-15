<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            $hasStudent = !is_null($user->student_number);
            $hasEmployee = !is_null($user->employee_id);

            if ($hasStudent && $hasEmployee) {
                throw new \InvalidArgumentException('A user cannot be linked to both a student and an employee.');
            }

            if (!$hasStudent && !$hasEmployee) {
                throw new \InvalidArgumentException('A user must be linked to either a student (student_number) or an employee (employee_id).');
            }
        });
    }

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'status',
        'must_change_password',
        'student_number',
        'employee_id',
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
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Get the roles that belong to this user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('role_name', $roleName)->exists();
    }

    /**
     * Check if the user has any of the given roles.
     *
     * @param array<string> $roleNames
     * @return bool
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('role_name', $roleNames)->exists();
    }

    /**
     * Get the student associated with this user.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the employee associated with this user.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Get the notifications for this user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'user_id');
    }

    /**
     * Scope a query to only include student users.
     */
    public function scopeStudents($query)
    {
        return $query->whereNotNull('student_number');
    }

    /**
     * Get the user's display name (for layouts).
     * Returns the user's email.
     *
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->email;
    }
}
