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
        $user->load([
            'groups' => function ($query) {
                $query->select('groups.id', 'name');
                $query->with([
                    'files' => function ($query) {
                        if (request()->status)
                            $query->where('status', request()->status);
                        $query->select('id', 'status', 'group_id', 'name');
                    }
                ]);
            }
        ]);
        return $user;
    }

    public function allFiles() {
       return $this->sendResponse(File::query()->get());
    }

    public function getCheckInFiles()
    {
        $user = Auth::user();
        return $user->load([
            'files' => function ($query) {
                $query->where('status', FileStatusEnum::IN->value)->select('files.id', 'files.status', 'files.group_id', 'files.name');
            }
        ]);
    }

    public function store(StoreFileRequest $request)
    {
        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
        $originalFileName = pathinfo($originalFileName, PATHINFO_FILENAME);
        $data = [
            'group_id' => $request->group_id,
            'path' => FileHelper::upload($request->file, $request->group_id)
        ];
        $data['name'] = $originalFileName;

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
                $requestFile = $request->file('file');
                $originalFileName = $requestFile->getClientOriginalName();
                $originalFileName = pathinfo($originalFileName, PATHINFO_FILENAME);
                $file->update([
                    'path' => $path,
                    'status' => FileStatusEnum::OUT->value,
                    // 'name' => $originalFileName
                ]);
                DB::table('user_file')
                ->where('file_id', $file->id)
                ->where('user_id', auth()->id())
                ->update([
                    'updated_at' => now()
                ]);
            } elseif ($request->type == FileStatusEnum::IN->value) {
                DB::beginTransaction();
                
                $files = File::query()->whereIn('id', $request->ids)->get()->map(function ($filePath) {
                    return [
                        'path' => asset($filePath->path),
                        'name' => $filePath->name
                    ];
                })->toArray();
                $user = Auth::user();
                $user->files()->attach($request->ids, ['created_at' => now()]);
                File::query()->whereIn('id', $request->ids)
                    ->update([
                        'status' => FileStatusEnum::IN->value,
                    ]);
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
                    ->where('status', FileStatusEnum::OUT->value)
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
            $pivotRecords = User::find($request->user_id, ['id', 'name'])->load([
                'files' => function ($q) {
                    $q->select('files.id', 'files.name','path', 'user_file.created_at as checkin_time', 'user_file.updated_at as checkout_time');
                }
            ]);

            $data['id'] = $pivotRecords['id'];
            $data['name'] = $pivotRecords['name'];

            foreach ($pivotRecords['files'] as $file) {

                $data['files'][] = [
                    'id' => $file['id'],
                    'path' => asset($file['path']),
                    'name' => $file['name'],
                    'checkin_time' => $file['checkin_time'],
                    'checkout_time' => $file['checkout_time']
                ];
            }
        } elseif ($request->file_id) {
            $pivotRecords = File::find($request->file_id, ['id', 'path', 'name'])->load([
                'users' => function ($q) {
                    $q->select('users.id', 'users.name','users.email', 'user_file.created_at as checkin_time', 'user_file.updated_at as checkout_time');
                }
            ]);
            $data['id'] = $pivotRecords['id'];
            $data['path'] = asset($pivotRecords['path']);
            $data['name'] = $pivotRecords['name'];

            foreach ($pivotRecords['users'] as $user) {

                $data['users'][] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'checkin_time' => $user['checkin_time'],
                    'checkout_time' => $user['checkout_time']
                ];
            }
        }

        return $data;
    }
}
