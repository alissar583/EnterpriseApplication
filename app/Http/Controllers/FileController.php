<?php

namespace App\Http\Controllers;

use App\Enums\FileStatusEnum;
use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Http\Requests\CheckOutInRequest;
use App\Http\Requests\StoreFileRequest;
use App\Models\File;
use App\Models\User;
use App\Models\UserFile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FileController extends Controller
{

    public function index()
    {
        request()->validate([
            'status' => Rule::enum(FileStatusEnum::class)
        ]);
        $user = auth()->user();
        $user->load(['groups' => function ($query) {
            $query->select('groups.id', 'name');
            $query->with(['files' => function ($query) {
                if (request()->status)
                    $query->where('status', request()->status);
                $query->select('id', 'status', 'group_id');
            }]);
        }]);
        return $user;
    }

    public function getCheckInFiles()
    {
        $user = Auth::user();
        return $user->load(['files' => function ($query) {
            $query->select('files.id', 'files.status', 'files.group_id');
        }]);
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

    public function checInOut(CheckOutInRequest $request)
    {
        $check = $this->checkPolicy(auth()->user(), $request->validated());
        if (!$check)
            return $this->sendError('This action is unauthorized.', [], 403);
        try {
            if ($request->type == FileStatusEnum::OUT->value) {
                $file = File::query()->find($request['ids'][0]);
                $path = FileHelper::replace($request->file, $file->path, $file->group_id);
                $file->update([
                    'path' => $path,
                    'status' => FileStatusEnum::OUT->value
                ]);
                DB::table('user_file')->update([
                    'updated_at' => now()
                ]);
            } elseif ($request->type == FileStatusEnum::IN->value) {
                DB::beginTransaction();
                File::query()->whereIn('id', $request->ids)
                    ->update([
                        'status' => FileStatusEnum::IN->value,
                    ]);
                $files = File::query()->where('id', $request->ids)->pluck('path')->map(function ($filePath) {
                    return asset($filePath);
                })->toArray();
                $user = Auth::user();
                $user->files()->attach($request->ids, ['created_at' => now()]);
                DB::commit();
            }
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage(), [], 400);
        }
        return $this->sendResponse($files ?? []);
    }

    private function checkPolicy($user, $request)
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
                &&
                (UserFile::query()->where('file_id', $request['ids'][0])->latest()->value('user_id') == $user->id)
            );
        }
    }

    public function reports(Request $request)
    {
        $data = [];

        if ($request->user_id) {
            $pivotRecords =  User::find($request->user_id, ['id', 'name'])->load(['files' => function ($q) {
                $q->select('files.id', 'path', 'user_file.created_at as checkin_time', 'user_file.updated_at as checkout_time');
            }]);

            $data['id'] =  $pivotRecords['id'];
            $data['name'] = $pivotRecords['name'];

            foreach ($pivotRecords['files'] as $file) {

                $data['files'][] = [
                    'id' => $file['id'],
                    'path' => asset($file['path']),
                    'checkin_time' => $file['checkin_time'],
                    'checkout_time' => $file['checkout_time']
                ];
            }
        } elseif ($request->file_id) {
            $pivotRecords =  File::find($request->file_id, ['id', 'path'])->load(['users' => function ($q) {
                $q->select('users.id', 'users.name', 'user_file.created_at as checkin_time', 'user_file.updated_at as checkout_time');
            }]);
            $data['id'] =  $pivotRecords['id'];
            $data['path'] = asset($pivotRecords['path']);

            foreach ($pivotRecords['users'] as $user) {

                $data['users'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'checkin_time' => $user['checkin_time'],
                    'checkout_time' => $user['checkout_time']
                ];
            }
        }

        return $data;
    }
}
