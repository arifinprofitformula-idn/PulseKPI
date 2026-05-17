<?php

namespace App\Actions\Users;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ValidateUserOrganizationHierarchy
{
    public function execute(User $user): void
    {
        $errors = [];

        if ($user->division_id !== null && $user->department_id !== null) {
            $departmentDivisionId = $this->resolveDepartmentDivisionId($user);

            if ($departmentDivisionId !== $user->division_id) {
                $errors['department_id'] = 'The selected department must belong to the selected division.';
            }
        }

        if ($user->department_id !== null && $user->position_id !== null) {
            $positionDepartmentId = $this->resolvePositionDepartmentId($user);

            if ($positionDepartmentId !== $user->department_id) {
                $errors['position_id'] = 'The selected position must belong to the selected department.';
            }
        }

        if (($user->supervisor_id !== null) && ($user->getKey() !== null) && ($user->supervisor_id === $user->getKey())) {
            $errors['supervisor_id'] = 'The selected supervisor must be another user.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function resolveDepartmentDivisionId(User $user): ?int
    {
        if ($user->relationLoaded('department') && $user->department instanceof Department) {
            return $user->department->division_id;
        }

        return Department::query()
            ->whereKey($user->department_id)
            ->value('division_id');
    }

    private function resolvePositionDepartmentId(User $user): ?int
    {
        if ($user->relationLoaded('position') && $user->position instanceof Position) {
            return $user->position->department_id;
        }

        return Position::query()
            ->whereKey($user->position_id)
            ->value('department_id');
    }
}
