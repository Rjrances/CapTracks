<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class Notification extends Model
{
    protected $fillable = ['title', 'description', 'role', 'redirect_url', 'is_read', 'user_id'];
    protected $casts = [
        'is_read' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleToWebUser(Builder $query, User $user): Builder
    {
        $roles = self::resolveWebUserAudienceRoles($user->primary_role);

        return $query->where(function (Builder $notificationQuery) use ($user, $roles) {
            $notificationQuery->whereIn('role', $roles)
                ->orWhere('user_id', $user->id);
        });
    }

    public function scopeVisibleToCoordinatorWorkspace(Builder $query, User $user): Builder
    {
        $roles = array_values(array_unique(array_merge(
            ['coordinator'],
            self::resolveWebUserAudienceRoles($user->primary_role)
        )));

        return $query->where(function (Builder $notificationQuery) use ($user, $roles) {
            $notificationQuery->whereIn('role', $roles)
                ->orWhere('user_id', $user->id);
        });
    }

    public function scopeVisibleToStudent(
        Builder $query,
        Student $student,
        int|string|null $studentAccountId = null
    ): Builder {
        $targetIds = [];

        if ($studentAccountId !== null && $studentAccountId !== '' && is_numeric($studentAccountId)) {
            $targetIds[] = (int) $studentAccountId;
        }

        if (is_numeric($student->student_id)) {
            $targetIds[] = (int) $student->student_id;
        }

        $targetIds = array_values(array_unique($targetIds));

        return $query->where(function (Builder $notificationQuery) use ($targetIds) {
            $notificationQuery->where('role', 'student');

            if (!empty($targetIds)) {
                $notificationQuery->orWhereIn('user_id', $targetIds);
            }
        });
    }

    public static function resolveWebUserAudienceRoles(?string $role): array
    {
        if (in_array($role, ['teacher', 'adviser', 'panelist'], true)) {
            return ['teacher', 'adviser', 'panelist'];
        }

        if ($role === 'coordinator') {
            return ['coordinator'];
        }

        if ($role === 'chairperson') {
            return ['chairperson'];
        }

        return array_values(array_filter([$role]));
    }
}
