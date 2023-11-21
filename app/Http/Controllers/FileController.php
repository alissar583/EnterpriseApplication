<?php

namespace App\Http\Controllers;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\StoreFileRequest;
use App\Models\File;
use App\Models\User;

class FileController extends Controller
{

    public function index()
    {
        $user = User::query()->first(); // TODO auth user
        return $user->groups->files;
    }

    public function store(StoreFileRequest $request)
    {
        $data = [
            'group_id' => $request->group_id,
            'path' => FileHelper::upload($request->file, $request->group_id)
        ];
        File::query()->create($data);
        return ResponseHelper::success();
    }
}
