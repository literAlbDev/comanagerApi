<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function checkTasks(Request $request)
    {
        $tasks = Task::all();
        if ($request->user()->manager_id == null) {
            foreach ($tasks as $task) {
                if (!$request->user()->workers->contains($task->worker)) {
                    $tasks = $tasks->except($task->id);
                }
            }
            return TaskResource::collection($tasks);
        }

        $tasks = Task::whereBelongsTo($request->user(), 'worker')->get();
        //if ($tasks->isEmpty()) {
        //    return response()->json(['errors' => ["you do not have any tasks"]], 404);
        //}

        return TaskResource::collection($tasks);
    }


    public function checkTask(Request $request, $id)
    {
        $task = Task::find($id);
        if ($request->user()->manager_id == null && $request->user()->workers->contains($task->worker)) {
            return TaskResource::make($task);
        }

        $task = Task::whereBelongsTo($request->user(), 'worker')->find($id);
        //if (!$task) {
        //    return response()->json(['errors' => "you do not have a task with id: $id"], 404);
        //}

        return TaskResource::make($task);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request  $request)
    {
        return $this->checkTasks($request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'title' => 'required|string|unique:tasks,title|min:5|max:100',
            'description' => 'string|min:10|max:3000',
            'worker_id' => 'required|integer|exists:users,id'
        ]);
        if ($request->user()->manager_id != null) {
            return response()->json(['errors' => ['error' => "you do not have permission"]], 401);
        }
        if (User::find($request->worker_id)->manager_id == null) {
            return response()->json(['errors' => ['error' => "the id $request->worker_id does not belong to a worker"]], 404);
        }
        if (!$request->user()->workers->contains($request->worker_id) && $request->user()->manager_id == null) {
            return response()->json(['errors' => ['error' => "you do not have a woker with id $request->worker_id"]], 404);
        }

        $created_task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? 'pending',
            'worker_id' => $request->worker_id
        ]);

        return TaskResource::make($created_task);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Task $task)
    {
        return $this->checkTask($request, $task->id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Task $task)
    {
        request()->validate([
            'title' => "string|unique:tasks,title,$task->id|min:5|max:100",
            'description' => 'string|min:10|max:3000',
            'worker_id' => 'integer|exists:users,id'
        ]);
        if($request->worker_id){
            if (User::find($request->worker_id)->manager_id == null) {
                return response()->json(['errors' => ['error' => "the id $request->worker_id does not belong to a worker"]], 404);
            }
            if (!$request->user()->workers->contains($request->worker_id) && $request->user()->manager_id == null) {
                return response()->json(['errors' => ['error' => "you do not have a woker with id $request->worker_id"]], 404);
            }
        }

        $update_result = $task->update([
            'title' => $request->title ?? $task->title,
            'description' => $request->description ?? $task->description,
            'status' => $request->status ?? $task->status,
            'reason' => $request->reason ?? $task->reason,
            'worker_id' => $request->worker_id ?? $task->worker_id,
        ]);

        return $update_result ? TaskResource::make($task) : response()->json(['errors' => ['error' => 'an error occured']], 500);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Task $task)
    {
        $checkedTask = $this->checkTask($request, $task->id);
        if ($checkedTask instanceof JsonResponse) return $checkedTask;

        $delete_result = $task->delete();
        //TODO change the error message
        return $delete_result ? $checkedTask : response()->json(['errors' => ['error' => 'an error occured']], 500);
    }
}
