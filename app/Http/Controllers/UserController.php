<?php

namespace App\Http\Controllers;

use App\Models\ListModel;
use App\Models\Note;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::with(['createdTodos', 'lists', 'todos'])->get();
        return response()->json($users, 200);
    }

    public function findById(int $id): JsonResponse
    {
        $user = User::with(['createdTodos', 'lists', 'todos'])->where('id', $id)->first();
        return $user!==null ? response()->json($user, 200) : response()->json(null, 404);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $this->parseRequest($request);
        DB::beginTransaction();
        try {
            $user = User::create($data);

            if (isset($data['createdTodos']) && is_array($data['createdTodos'])) {
                foreach ($data['createdTodos'] as $todoData) {
                    if (isset($todoData['id'])) {
                        $todo = Todo::find($todoData['id']);
                        if ($todo) {
                            $user->createdTodos()->save($todo);
                        }
                    } else if (isset($todoData['title'])) {
                        $todo = Todo::firstOrCreate([
                            'title' => $todoData['title'],
                            'description' => $todoData['description'],
                            'dueDate' => $todoData['dueDate'],
                            'public' => $todoData['public'],
                            'image' => $todoData['image'],
                            'user_id' => $todoData['user_id'],
                            'note_id' => $todoData['note_id']
                        ]);
                        $user->createdTodos()->save($todo);
                    }
                }
            }

            // Verarbeiten der Todos, die mit dem Tag verknüpft werden sollen
            if (isset($data['todos']) && is_array($data['todos'])) {
                foreach ($data['todos'] as $todoData) {
                    if (is_array($todoData) && isset($todoData['id']) && is_numeric($todoData['id'])) {
                        $todo = Todo::find($todoData['id']);
                        if ($todo) {
                            $user->todos()->attach($todo->id);
                        }
                    } elseif (is_numeric($todoData)) {
                        $todo = Todo::find($todoData);
                        if ($todo) {
                            $user->todos()->attach($todo->id);
                        }
                    } else if (is_array($todoData) && isset($todoData['title'])) {
                        $todo = Todo::firstOrCreate([
                            'title' => $todoData['title'],
                            'description' => $todoData['description'],
                            'dueDate' => $todoData['dueDate'],
                            'public' => $todoData['public'],
                            'image' => $todoData['image'],
                            'user_id' => $todoData['user_id'],
                            'note_id' => $todoData['note_id']
                        ]);
                        $user->todos()->save($todo);
                    }
                }
            }

            if (isset($data['lists']) && is_array($data['lists'])) {
                foreach ($data['lists'] as $listData) {
                    if (is_array($listData) && isset($listData['id']) && is_numeric($listData['id']) && count($listData) === 1) {
                        $list = ListModel::find($listData['id']);
                        if ($list) {
                            $user->lists()->attach($list);
                        }
                    } elseif (is_numeric($listData)) {
                        $list = ListModel::find($listData);
                        if ($list) {
                            $user->lists()->attach($list);
                        }
                    } else if (is_array($listData) && isset($listData['name'])) {
                        $list = ListModel::firstOrCreate([
                            'name' => $listData['name'],
                            'public' => $listData['public']
                        ]);
                        $user->lists()->save($list);
                    }
                }
            }

            DB::commit();
            return response()->json($user, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('saving user failed: ' . $e->getMessage(), 420);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = User::with(['createdTodos', 'lists', 'todos'])->where('id', $id)->first();
            if ($user != null) {
                $data = $this->parseRequest($request);
                $newRequest = new Request($data);
                $user->update($newRequest->all());

                if (isset($data['todos']) && is_array($data['todos'])) {
                    foreach ($data['todos'] as $todoData) {
                        if (isset($todoData['id'])) {
                            $user->todos()->attach($todoData['id']);
                        }elseif(is_numeric($todoData)){
                            $todo = Todo::find($todoData);
                            if ($todo) {
                                $user->todos()->attach($todo);
                            }
                        }
                    }
                }

                if (isset($data['lists']) && is_array($data['lists'])) {
                    foreach ($data['lists'] as $listData) {
                        if (isset($listData['id'])) {
                            $user->lists()->attach($listData['id']);
                        }elseif(is_numeric($listData)){
                            $list = ListModel::find($listData);
                            if($list){
                                $user->lists()->attach($list);
                            }
                        }
                    }
                }

                if (isset($data['createdTodos']) && is_array($data['createdTodos'])) {
                    $createdTodosIds = [];
                    foreach ($data['createdTodos'] as $createdTodoData) {
                        if (isset($createdTodoData['id'])) {
                            array_push($createdTodosIds, $createdTodoData['id']);
                        }
                    }
                    $user->creator()->sync($createdTodosIds);
                }
                $user->save();
            }
            DB::commit();
            $user1 = User::with(['createdTodos', 'lists', 'todos'])->where('id', $id)->first();
            return response()->json($user1, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('updating user failed: ' . $e->getMessage(), 420);
        }
    }

    public function delete(int $id):JsonResponse
    {
        $user = User::where('id', $id)->first();
        if($user != null){
            $user->delete();
            return response()->json('user ('.$id.') succesfully deleted', 200);
        }else{
            return response()->json('could not delete user - it does not exist', 422);
        }
    }


    private function parseRequest(Request $request): array
    {
        $date = new \DateTime($request->updated_at);
        $data = $request->all();
        $data['updated_at'] = $date->format('Y-m-d H:i:s');
        return $data;
    }
}
