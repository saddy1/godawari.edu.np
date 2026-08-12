<?php

namespace App\Models\Work;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'work_checklist_id',
        'work_checklist_item_id',
        'description',
        'category',
        'due_date',
        'max_score',
        'incentive_amount',
        'assignment_type',
        'assigned_user_id',
        'work_group_id',
        'group_submission_mode',
        'group_payment_mode',
        'late_penalty_percent',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'incentive_amount' => 'decimal:2',
        'late_penalty_percent' => 'decimal:2',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checklist()
    {
        return $this->belongsTo(WorkChecklist::class, 'work_checklist_id');
    }

    public function checklistItem()
    {
        return $this->belongsTo(WorkChecklistItem::class, 'work_checklist_item_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function group()
    {
        return $this->belongsTo(WorkGroup::class, 'work_group_id');
    }

    public function submissions()
    {
        return $this->hasMany(WorkTaskSubmission::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->canAccess('work-tasks.review') || $user->canAccess('work-tasks.create')) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($user) {
            $visible->where('assigned_user_id', $user->id)
                ->orWhereHas('group.members', fn (Builder $members) => $members->where('users.id', $user->id));
        });
    }

    public function scopePendingForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $pending) use ($user) {
            $pending->where(function (Builder $individual) use ($user) {
                $individual->where('assignment_type', 'individual')
                    ->where('assigned_user_id', $user->id)
                    ->whereDoesntHave('submissions', fn (Builder $submissions) => $submissions->where('user_id', $user->id));
            })->orWhere(function (Builder $sharedGroup) use ($user) {
                $sharedGroup->where('assignment_type', 'group')
                    ->where('group_submission_mode', 'shared')
                    ->whereHas('group.members', fn (Builder $members) => $members->where('users.id', $user->id))
                    ->whereDoesntHave('submissions', fn (Builder $submissions) => $submissions->whereNull('user_id'));
            })->orWhere(function (Builder $individualGroup) use ($user) {
                $individualGroup->where('assignment_type', 'group')
                    ->where('group_submission_mode', 'individual')
                    ->whereHas('group.members', fn (Builder $members) => $members->where('users.id', $user->id))
                    ->whereDoesntHave('submissions', fn (Builder $submissions) => $submissions->where('user_id', $user->id));
            });
        });
    }

    public function isAssignedTo(User $user): bool
    {
        if ((int) $this->assigned_user_id === (int) $user->id) {
            return true;
        }

        if ($this->relationLoaded('group') && $this->group) {
            return $this->group->members->contains('id', $user->id);
        }

        return $this->group?->members()->where('users.id', $user->id)->exists() ?? false;
    }

    public function isSharedGroupTask(): bool
    {
        return $this->assignment_type === 'group' && $this->group_submission_mode === 'shared';
    }
}
