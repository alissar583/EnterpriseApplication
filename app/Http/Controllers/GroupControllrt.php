<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupControllrt extends Controller
{
    public function index()
    {
        $groups = Group::query()->with('users', function($query) {
            $query->where('is_admin', false);
        })->get();
        return $this->sendResponse($groups);
    }

    public function addUsers(Request $request, Group $group)
    {
        $request->validate([
            'users' => ['array', 'required', 'exists:users,id']
        ]);
        $group->users()->attach($request['users']);
        return $this->sendResponse([]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['string', 'required', 'unique:groups,name']
        ]);
        $group = Group::query()->create($data);
        $userId = auth()->id();
        $group->users()->attach([$userId]);
        $group->refresh()->load(['users' => function($query) {
            $query->where('is_admin', false);
        }]);
        return $this->sendResponse($group);
    }

}
