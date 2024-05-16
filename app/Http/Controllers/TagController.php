<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Todo;
use Illuminate\Http\Request;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use PHPUnit\Exception;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::with(['todos', 'notes'])->get();
        return response()->json($tags, 200);
    }

    public function findById(int $id): JsonResponse
    {
        $tag = Tag::with(['todos', 'notes'])->where('id', $id)->first();
        return $tag!==null ? response()->json($tag, 200) : response()->json(null, 404);
    }

    public function findBySearchTerm(string $searchTerm): JsonResponse
    {
        $tags = Tag::with(['todos', 'notes'])
            ->where('name', 'LIKE', '%'.$searchTerm.'%')
            ->orWhereHas('todos', function ($query) use ($searchTerm) {
                $query->where('title', 'LIKE', '%'.$searchTerm.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchTerm.'%');
            })
            ->orWhereHas('notes', function ($query) use ($searchTerm) {
                $query->where('title', 'LIKE', '%'.$searchTerm.'%')
                    ->orWhere('description', 'LIKE', '%'.$searchTerm.'%');
            })
            ->get();

        return response()->json($tags, 200);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $this->parseRequest($request);
        DB::beginTransaction();
        try {
            $tag = Tag::create($data);

            // Verarbeiten der Notes, die mit dem Tag verknüpft werden sollen
            if (isset($data['notes']) && is_array($data['notes'])) {
                foreach ($data['notes'] as $noteData) {
                    if (is_array($noteData) && isset($noteData['id']) && is_numeric($noteData['id'])) {
                        $note = Note::find($noteData['id']);
                        if ($note) {
                            $tag->notes()->attach($note);
                        }
                    } elseif (is_numeric($noteData)) {
                        $note = Note::find($noteData);
                        if ($note) {
                            $tag->notes()->attach($note);
                        }
                    } else if (isset($noteData['title'])) {
                        $note = Note::firstOrCreate([
                            'title' => $noteData['title'],
                            'description' => $noteData['description'],
                            'image' => $noteData['image'],
                            'list_id' => $noteData['list_id']
                        ]);
                        $tag->notes()->save($note);
                    }
                }
            }

            // Verarbeiten der Todos, die mit dem Tag verknüpft werden sollen
            if (isset($data['todos']) && is_array($data['todos'])) {
                foreach ($data['todos'] as $todoData) {
                    if (is_array($todoData) && isset($todoData['id']) && is_numeric($todoData['id'])) {
                        $todo = Todo::find($todoData['id']);
                        if ($todo) {
                            $tag->todos()->attach($todo);
                        }
                    } elseif (is_numeric($todoData)) {
                        $todo = Todo::find($todoData);
                        if ($todo) {
                            $tag->todos()->attach($todo);
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
                        $tag->todos()->save($todo);
                    }
                }
            }

            DB::commit();
            return response()->json($tag, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('saving tag failed: ' . $e->getMessage(), 420);
        }
    }


    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $tag = Tag::with('notes', 'todos')->where('id', $id)->first();
            if ($tag != null) {
                $data = $this->parseRequest($request);
                $newRequest = new Request($data);  // Erstellen eines neuen Request-Objekts
                $tag->update($newRequest->all());  // Verwenden von all() auf dem neuen Request-Objekt

                // Verarbeiten von Todo-IDs, wenn vorhanden
                if (isset($data['todos']) && is_array($data['todos'])) {
                    $todoIds = [];
                    foreach ($data['todos'] as $todoData) {
                        if (is_array($todoData) && isset($todoData['id']) && is_numeric($todoData['id'])) {
                            if (Todo::find($todoData['id'])) {
                                $todoIds[] = $todoData['id'];
                            }
                        } elseif (is_numeric($todoData)) {
                            if (Todo::find($todoData)) {
                                $todoIds[] = $todoData;
                            }
                        }
                    }
                    $tag->todos()->sync($todoIds);
                }

                // Verarbeiten von Note-IDs, wenn vorhanden
                if (isset($data['notes']) && is_array($data['notes'])) {
                    $noteIds = [];
                    foreach ($data['notes'] as $noteData) {
                        if (is_array($noteData) && isset($noteData['id']) && is_numeric($noteData['id'])) {
                            if (Note::find($noteData['id'])) {
                                $noteIds[] = $noteData['id'];
                            }
                        } elseif (is_numeric($noteData)) {
                            if (Note::find($noteData)) {
                                $noteIds[] = $noteData;
                            }
                        }
                    }
                    $tag->notes()->sync($noteIds);
                }

                $tag->save();
            }

            DB::commit();
            $tag1 = Tag::with('notes', 'todos')->where('id', $id)->first();
            return response()->json($tag1, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('updating tag failed: ' . $e->getMessage(), 420);
        }
    }


    public function delete(int $id):JsonResponse
    {
        $tag = Tag::where('id', $id)->first();
        if($tag!=null){
            $tag->delete();
            return response()->json('tag ('.$id.') succesfully deleted', 200);
        }else{
            return response()->json('could not delete tag - it does not exist', 422);
        }
    }



    private function parseRequest(Request $request): array
    {
        // Konvertierung von ISO 8601 Datum
        $date = new \DateTime($request->updated_at);
        $data = $request->all();
        $data['updated_at'] = $date->format('Y-m-d H:i:s');
        return $data;
    }
}
