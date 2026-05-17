<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function __invoke(User $user): View
    {
        return view('users.show', [
            'user' => $user->load([
                'division',
                'department.division',
                'position',
                'supervisor',
            ]),
        ]);
    }
}
