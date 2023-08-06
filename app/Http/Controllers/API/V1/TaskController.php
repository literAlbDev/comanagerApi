<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

use function PHPSTORM_META\type;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TaskResource::collection(Task::all());
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
        if (User::find($request->worker_id)->manager_id == null) {
            return response()->json(['message' => "the id $request->worker_id does not belong to a worker"]);
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
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show(Task $task)
    {
        return TaskResource::make($task);
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
            'title' => "required|string|unique:tasks,title,$task->id|min:5|max:100",
            'description' => 'string|min:10|max:3000',
            'worker_id' => 'integer|exists:users,id'
        ]);
        if (User::find($request->worker_id)->manager_id == null) {
            return response()->json(['message' => "the id $request->worker_id does not belong to a worker"]);
        }

        $update_result = $task->update([
            'title' => $request->title ?? $task->title,
            'description' => $request->description ?? $task->description,
            'status' => $request->status ?? $task->status,
            'worker_id' => $request->worker_id ?? $task->worker_id,
        ]);

        return $update_result ? TaskResource::make($task) : response()->json(['message' => 'an error occured']);;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function destroy(Task $task)
    {
        $delete_result = $task->delete();
        //TODO change the error message
        return $delete_result ? TaskResource::make($task) : response()->json(['message' => 'an error occured']);
    }
}
