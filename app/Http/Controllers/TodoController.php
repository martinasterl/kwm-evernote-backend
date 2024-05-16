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

class TodoController extends Controller
{
    public function index(): JsonResponse
    {
        $todos = Todo::with(['note', 'creator', 'tags', 'users'])->get();
        return response()->json($todos, 200);
    }

    public function findById(int $id): JsonResponse
    {
        $todo = Todo::with(['note', 'creator', 'tags', 'users'])->where('id', $id)->first();

        if ($todo) {
            // Konvertiere 'public' zurück zu 'isPublic' für das Frontend
            $todo->isPublic = $todo->public;
        }

        return $todo !== null ? response()->json($todo, 200) : response()->json(null, 404);
    }


    public function findBySearchTerm(string $searchTerm): JsonResponse
    {
        $todos = Todo::with(['note', 'creator', 'tags', 'users'])
            ->where('title', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('description', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('dueDate', 'LIKE', '%' . $searchTerm . '%')
            ->get();
        return response()->json($todos, 200);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $this->parseRequest($request);
        DB::beginTransaction();
        try {
            $todo = Todo::create($data);

            // Tags behandeln
            if (isset($data['tags']) && is_array($data['tags'])) {
                $tagIds = [];
                foreach ($data['tags'] as $tagData) {
                    if (is_array($tagData)) {
                        if (isset($tagData['id']) && Tag::find($tagData['id'])) {
                            $tagIds[] = $tagData['id'];
                        } elseif (isset($tagData['name'])) {
                            $tag = Tag::firstOrCreate(['name' => $tagData['name']]);
                            $tagIds[] = $tag->id;
                        }
                    } elseif (is_numeric($tagData)) {  // Überprüfen, ob $tagData eine numerische ID ist
                        if (Tag::find($tagData)) {
                            $tagIds[] = $tagData;
                        }
                    }
                }
                $todo->tags()->sync($tagIds); // Verwendet `sync` anstelle von `attach` für korrektes Update
            }



            if (isset($data['note']) && is_array($data['note'])) {
                foreach ($data['note'] as $noteData) {
                    if (isset($noteData['id']) && count($noteData) === 1) {
                        $note = Note::find($noteData['id']);
                        if ($note) {
                            $todo->notes()->attach($note);
                        }
                    } else if (isset($noteData['name'])) {
                        $note = Note::firstOrCreate([
                            'title' => $noteData['title'],
                            'description' => $noteData['description'],
                            'image' => $noteData['image'],
                            'list_id' => $noteData['list_id']
                        ]);
                        $todo->note()->save($note);
                    }
                }
            }

            if (isset($data['users']) && is_array($data['users'])) {
                $userIds = [];
                foreach ($data['users'] as $userData) {
                    if (is_array($userData)) {
                        if (isset($userData['id']) && is_numeric($userData['id']) && User::find($userData['id'])) {
                            $userIds[] = $userData['id'];
                        } elseif (isset($userData['firstName'], $userData['lastName'], $userData['username'], $userData['password'])) {
                            $user = User::firstOrCreate([
                                'firstName' => $userData['firstName'],
                                'lastName' => $userData['lastName'],
                                'username' => $userData['username'],
                                'password' => bcrypt($userData['password'])
                            ]);
                            $userIds[] = $user->id;
                        }
                    } elseif (is_numeric($userData)) {
                        if (User::find($userData)) {
                            $userIds[] = $userData;
                        }
                    }
                }
            }

            if (isset($data['creator']) && is_array($data['creator'])) {
                foreach ($data['creator'] as $cr) {
                    if (isset($cr['id']) && count($cr) === 1) {
                        $creator = User::find($cr['id']);
                        if ($creator) {
                            $todo->creator()->attach($creator);
                        }
                    } elseif (isset($us['username']))  {
                        $creator = User::firstOrCreate([
                            'firstName' => $cr['firstName'],
                            'lastName' => $cr['lastName'],
                            'username' => $cr['username'],
                            'password' => $cr['password']
                        ]);
                        $todo->creator()->save($creator);
                    }
                }
            }
            DB::commit();
            return response()->json($todo, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('saving todo failed: ' . $e->getMessage(), 420);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $todo = Todo::with(['note', 'creator', 'tags', 'users'])->where('id', $id)->first();
            if ($todo != null) {
                $data = $this->parseRequest($request);
                $newRequest = new Request($data);
                $todo->update($newRequest->all());

                if (isset($data['note']) && is_array($data['note'])) {
                    $noteIds = [];
                    foreach ($data['note'] as $noteData) {
                        if (isset($noteData['id'])) {
                            array_push($noteIds, $noteData['id']);
                        }
                    }
                    $todo->note()->sync($noteIds);
                }

                if (isset($data['tags']) && is_array($data['tags'])) {
                    $todo->tags()->sync([]);
                    foreach ($data['tags'] as $tagData) {
                        // Überprüfen, ob $tagData ein Array mit einer 'id' ist oder direkt eine ID als Zahl
                        if (is_array($tagData) && isset($tagData['id']) && is_numeric($tagData['id'])) {
                            if (Tag::find($tagData['id'])) {
                                $todo->tags()->attach($tagData['id']);
                            }
                        } elseif (is_numeric($tagData)) {
                            if (Tag::find($tagData)) {
                                $todo->tags()->attach($tagData);
                            }
                        }
                    }
                }

                if (isset($data['users']) && is_array($data['users'])) {
                    $todo->users()->sync([]);
                    foreach ($data['users'] as $userData) {
                        if (is_array($userData) && isset($userData['id']) && is_numeric($userData['id'])) {
                            if (User::find($userData['id'])) {
                                $todo->users()->attach($userData['id']);
                            }
                        } elseif (is_numeric($userData)) {
                            if (User::find($userData)) {
                                $todo->users()->attach($userData);
                            }
                        }
                    }
                }

                if (isset($data['creator']) && is_array($data['creator'])) {
                    $creatorIds = [];
                    foreach ($data['creator'] as $creatorData) {
                        if (isset($creatorData['id'])) {
                            array_push($creatorIds, $creatorData['id']);
                        }
                    }
                    $todo->creator()->sync($creatorIds);
                }
                $todo->save();
            }
            DB::commit();
            $todo1 = Todo::with(['note', 'creator', 'tags', 'users'])->where('id', $id)->first();
            return response()->json($todo1, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('updating tag failed: ' . $e->getMessage(), 420);
        }
    }

    public function delete(int $id):JsonResponse
    {
        $todo = Todo::where('id', $id)->first();
        if($todo != null){
            $todo->delete();
            return response()->json('todo ('.$id.') succesfully deleted', 200);
        }else{
            return response()->json('could not delete todo - it does not exist', 422);
        }
    }

    private function parseRequest(Request $request): array
    {
        $date = new \DateTime($request->updated_at);
        $data = $request->all();
        $data['updated_at'] = $date->format('Y-m-d H:i:s');

        // Überprüfen, ob das 'isPublic'-Feld gesetzt ist und es dem 'public'-Feld zuweisen
        if (isset($data['isPublic'])) {
            $data['public'] = $data['isPublic'];
            unset($data['isPublic']);
        }

        return $data;
    }

}
