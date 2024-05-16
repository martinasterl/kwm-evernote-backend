<?php

namespace App\Http\Controllers;

use App\Models\ListModel;
use App\Models\Note;
use App\Models\Tag;
use App\Models\Todo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    public function index(): JsonResponse
    {
        $notes = Note::with(['list', 'tags'])->get();
        return response()->json($notes, 200);
    }

    public function findBySearchTerm(string $searchTerm): JsonResponse
    {
        $notes = Note::with(['list', 'tags'])
            ->where('title', 'LIKE', '%'.$searchTerm.'%')->get();
        return response()->json($notes, 200);
    }

    public function save(Request $request): JsonResponse
    {
        $data = $this->parseRequest($request);
        DB::beginTransaction();
        try {
            $note = Note::create($data);

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
                    } elseif (is_numeric($tagData)) {
                        if (Tag::find($tagData)) {
                            $tagIds[] = $tagData;
                        }
                    }
                }
                $note->tags()->sync($tagIds);
            }

            if (isset($data['list']) && is_array($data['list'])) {
                foreach ($data['list'] as $listData) {
                    if (isset($listData['id']) && count($listData) === 1) {
                        $list = ListModel::find($listData['id']);
                        if ($list) {
                            $note->list()->attach($list);
                        }
                    } else if (isset($listData['name'])) {
                        $list = ListModel::firstOrCreate([
                            'name' => $listData['name'],
                            'public' => $listData['public']
                        ]);
                        $note->list()->save($list);
                    }
                }
            }

            DB::commit();
            return response()->json($note, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('saving note failed: ' . $e->getMessage(), 420);
        }
    }


    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $note = Note::with('list', 'tags')->where('id', $id)->first();
            if ($note != null) {
                $data = $this->parseRequest($request);
                $newRequest = new Request($data);
                $note->update($newRequest->all());

                if (isset($data['list']) && is_array($data['list'])) {
                    $listIds = [];
                    foreach ($data['list'] as $listData) {
                        if (isset($listData['id'])) {
                            array_push($listIds, $listData['id']);
                        }
                    }
                    $note->list()->sync($listIds);
                }

                // Verarbeiten von Note-IDs, wenn vorhanden
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $note->tags()->sync([]);
                    foreach ($data['tags'] as $tagData) {
                        if (is_array($tagData) && isset($tagData['id']) && is_numeric($tagData['id'])) {
                            if (Tag::find($tagData['id'])) {
                                $note->tags()->attach($tagData['id']);
                            }
                        } elseif (is_numeric($tagData)) {
                            if (Tag::find($tagData)) {
                                $note->tags()->attach($tagData);
                            }
                        }
                    }
                }

                $note->save();
            }

            DB::commit();
            $note1 = Note::with('list', 'tags')->where('id', $id)->first();
            return response()->json($note1, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('updating note failed: ' . $e->getMessage(), 420);
        }
    }


    public function delete(int $id):JsonResponse
    {
        $note = Note::where('id', $id)->first();
        if($note!=null){
            $note->delete();
            return response()->json('note ('.$id.') succesfully deleted', 200);
        }else{
            return response()->json('could not delete note - it does not exist', 422);
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
