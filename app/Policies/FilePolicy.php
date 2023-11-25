<?php

namespace App\Policies;

use App\Enums\FileStatusEnum;
use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, File $file): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, $request): bool
    {
        $groupes = File::query()->whereIn('id', $request['ids'])->pluck('group_id')->toArray();
        if ($request['type'] == FileStatusEnum::IN->value) {
            return (
                $user->groups()
                ->whereIn(
                    'groups.id',
                    $groupes
                )
                ->exists()
                &&
                File::query()
                ->whereIn('id', $request['ids'])
                ->where('status',  FileStatusEnum::OUT->value)
                ->count() === count($request['ids'])
            );
        } else {
            return (
                $user->groups()
                ->whereIn(
                    'groups.id',
                    $groupes
                )
                ->exists()
                &&
                File::query()
                ->whereIn('id', $request['ids'])
                ->where('status', '=', FileStatusEnum::IN->value)
                ->count() === count($request['ids'])
            );
        }
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, File $file): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, File $file): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, File $file): bool
    {
        //
    }
}
