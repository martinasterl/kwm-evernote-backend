<?php

namespace App\Http\Controllers;

use App\Models\ListModel;
use App\Models\Tag;
use App\Models\Todo;
use http\Client\Curl\User;
use http\Env\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Exception;

/**
 * Der Controller vermittelt dzwischen den Modellen, die die Daten
 * verwalten, und den Views, die die Benutzeroberfläche darstellen,
 * indem er bestimmt, welche Antwort auf eine Anfrage zurückgegeben wird.
 */

class ListModelController extends Controller
{
    /**
     * Gibt alle Listen zusammen mit ihren Nutzern, Notizen und Tags zurück.
     * Nutzt Eager Loading zur Optimierung der Datenbankabfragen.
     * Eager Loading ist eine ORM-Technik zum vorzeitigen Laden verwandter Daten
     * in einer einzigen Abfrage, um mehrfache Datenbankanfragen zu vermeiden.
     */
    public function index(): JsonResponse
    {
        $lists = ListModel::with(['users', 'notes', 'notes.tags'])->get(); //Tabellen welche mitgeladen werden müssen also in einer Beziehung stehen
        return response()->json($lists, 200);
    }

    /**
     * Findet eine Liste nach ihrer ID und lädt zugehörige Details.
     */
    public function findById(int $id): JsonResponse
    {
        $list = ListModel::with(['users', 'notes', 'notes.tags', 'notes.todos'])->where('id', $id)->first();

        //Konvertiert das Datenfeld 'public' (laravel) zu 'isPublic' (Angular) für Konsistenz im Frontend.
        if ($list) {
            $list->isPublic = $list->public;
        }

        return $list!==null ? response()->json($list, 200) : response()->json(null, 404);

    }

    /**
     * Überprüft, ob eine Liste mit der gegebenen ID existiert.
     */
    public function checkId(int $id): JsonResponse
    {
        $list = ListModel::where('id', $id)->first();
        return $list!==null ? response()->json(true, 200) : response()->json(false, 404);
    }

    /**
     * Sucht Listen nach einem Suchbegriff in Namen oder Benutzerdaten.
     */
    public function findBySearchTerm(string $searchTerm): JsonResponse
    {
        $lists = ListModel::with(['users', 'notes'])
            ->where('name', 'LIKE', '%'.$searchTerm.'%')
            ->orWhereHas('users', function ($query) use ($searchTerm) {
                $query->where('firstName', 'LIKE', '%'.$searchTerm.'%')
                    ->orWhere('lastName', 'LIKE', '%'.$searchTerm.'%');
            })
            ->get();

        return response()->json($lists, 200);
    }

    /**
     * Speichert eine neue Liste und die zugehörigen Benutzer, Notizen und Tags.
     * Nutzt Transaktionen, um die Konsistenz der Datenoperationen zu sichern.
     * Transaktionen ist eine Sammlung von Datenbankoperationen - alles wird als gesamtes
     * ausgeführt. Bei Fehlern wird ein Rollback durchgeführt.
     */
    public function save(Request $request): JsonResponse
    {
        $data = $this->parseRequest($request);
        // Starten einer DB Transaktion
        DB::beginTransaction();
        try {
            $list = ListModel::create($data);

            if (isset($data['users']) && is_array($data['users'])) {
                foreach ($data['users'] as $us) {
                    if (isset($data['users']) && is_array($data['users'])) {
                        foreach ($data['users'] as $us) {
                            if (isset($us['id']) && count($us) === 1) {
                                $user = \App\Models\User::find($us['id']);
                                if ($user) {
                                    $list->users()->save($user);
                                }else{
                                    return response()->json('could not add user - it does not exist');
                                }
                            } elseif(is_numeric($us)){
                                $user = \App\Models\User::find($us);
                                if($user){
                                    $list->users()->save($user);
                                }
                            }else {
                                $user = \App\Models\User::firstOrCreate([
                                    'firstName' => $us['firstName'],
                                    'lastName' => $us['lastName'],
                                    'username' => $us['username'],
                                    'password' => $us['password']
                                ]);
                                $list->users()->save($user);
                            }
                        }
                    }
                }
            }

            if (isset($data['notes']) && is_array($data['notes'])) {
                foreach ($data['notes'] as $not) {
                    $not['list_id'] = $list->id; //  Liste-ID setzen um Notiz mit Liste zu verknüpfen
                    // Überprüfen, ob die Notiz existiert oder nicht
                    if (isset($not['id']) && count($not) === 1) {
                        // Notiz existiert bereits
                        $note = \App\Models\Note::find($not['id']);
                        if ($note) {
                            $list->notes()->save($note);
                        } else {
                            return response()->json('Could not add note - it does not exist');
                        }
                    } else {
                        // Notiz existiert nicht
                        $note = \App\Models\Note::create([
                            'title' => $not['title'],
                            'description' => $not['description'],
                            'image' => $not['image'] ?? null,
                            'list_id' => $not['list_id']
                        ]);
                        // Verarbeiten der Tags für jede Notiz
                        if (isset($not['tags']) && is_array($not['tags'])) {
                            $tagIds = [];
                            foreach ($not['tags'] as $tagId) {
                                if (is_numeric($tagId) && Tag::find($tagId)) {
                                    $tagIds[] = $tagId;
                                }
                            }
                            if (count($tagIds) > 0) {
                                $note->tags()->sync($tagIds);
                            } else {
                                // Fehlerbehandlung, wenn keine gültigen Tag-IDs vorhanden sind
                                return response()->json(['error' => 'Invalid tag IDs'], 400);
                            }
                        }

                        // Verarbeiten der Todos
                        if (isset($not['todos']) && is_array($not['todos'])) {
                            foreach ($not['todos'] as $todoId) {
                                $todo = Todo::find($todoId);
                                if ($todo) {
                                    $todo->note_id = $note->id; // Aktualisieren der note_id des Todos
                                    $todo->save(); // Speichern des aktualisierten Todos
                                }
                            }
                        }

                        $list->notes()->save($note);
                    }
                }
            }

            DB::commit();
            return response()->json($list, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('saving list failed: ' . $e->getMessage(), 420);
        }
    }

    /**
     * Aktualisiert eine bestehende Liste und deren zugehörige Details.
     * attach() fügt in einer Many-to-Many-Beziehung neue Beziehungseinträge hinzu, ohne bestehende zu beeinflussen.
     * sync() aktualisiert Beziehungen in einer Many-to-Many-Beziehung, indem es nur die spezifizierten Beziehungen erhält und andere entfernt.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $list = ListModel::with('users', 'notes')->where('id', $id)->first();
            if ($list != null) {
                $data = $this->parseRequest($request);
                $newRequest = new Request($data);  // Erstellen eines neuen Request-Objekts
                $list->update($newRequest->all());  // Verwenden von all() auf dem neuen Request-Objekt

                $userIds = [];
                if (isset($data['users']) && is_array($data['users'])) {
                    foreach ($data['users'] as $us) {
                        if (isset($us['id'])) {
                            array_push($userIds, $us['id']);
                        } elseif(is_numeric($us)){
                            array_push($userIds, $us);
                        }else {
                            return response()->json('could not add user - it does not exist');
                        }
                    }
                }
                $list->users()->sync($userIds);

                if (isset($data['notes']) && is_array($data['notes'])) {
                    foreach ($data['notes'] as $noteData) {
                        if (isset($noteData['id'])) {
                            $note = \App\Models\Note::find($noteData['id']);
                        } else {
                            $note = \App\Models\Note::firstOrCreate([
                                'title' => $noteData['title'],
                                'description' => $noteData['description'],
                                'image' => $noteData['image'],
                                'list_id' => $noteData['list_id']
                            ]);

                            // Verarbeiten der Tags für jede Notiz
                            if (isset($noteData['tags']) && is_array($noteData['tags'])) {
                                $tagIds = [];
                                foreach ($noteData['tags'] as $tagId) {
                                    if (is_numeric($tagId) && Tag::find($tagId)) {
                                        $tagIds[] = $tagId;
                                    }
                                }
                                if (count($tagIds) > 0) {
                                    $note->tags()->sync($tagIds);
                                }
                            }

                            // Verarbeiten der Todos für jede Notiz
                            if (isset($noteData['todos']) && is_array($noteData['todos'])) {
                                foreach ($noteData['todos'] as $todoId) {
                                    $todo = Todo::find($todoId);
                                    if ($todo) {
                                        $todo->note_id = $note->id;
                                        $todo->save();
                                    }
                                }
                            }

                        }

                        $list->notes()->save($note);

                    }
                }
                $list->save();
            }

            DB::commit();
            $list1 = ListModel::with('users')->where('id', $id)->first();
            return response()->json($list1, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json('updating list failed: ' . $e->getMessage(), 420);
        }
    }

    /**
     * Löscht eine Liste anhand ihrer ID.
     */
    public function delete(int $id):JsonResponse
    {
        $list = ListModel::where('id', $id)->first();
        if($list!=null){
            $list->delete();
            return response()->json('list ('.$id.') succesfully deleted', 200);
        }else{
            return response()->json('could not delete list - it does not exist', 422);
        }
    }



    private function parseRequest(Request $request): array
    {
        // Konvertierung von ISO 8601 Datum
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

