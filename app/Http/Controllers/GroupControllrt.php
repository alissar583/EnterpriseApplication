<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupControllrt extends Controller
{
    public function index()
    {
        $groups = Group::query()->get();
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
            'name' => ['string', 'required']
        ]);
        $group = Group::query()->create($data);
        $userId = auth()->id();
        $group->users()->attach([$userId]);
        return $this->sendResponse($group);
    }

}
