<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TodosTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $todo = new Todo();
        $todo->title = 'Todo 1';
        $todo->description = 'Test von Todo 1';
        $todo->dueDate = date("Y-m-d H:i:s");

        $note = Note::first();
        $todo->note()->associate($note);

        $todo->save();

        $users = User::all()->pluck("id");
        $todo->users()->sync($users);

        $tags = Tag::all()->pluck("id");
        $todo->tags()->sync($tags);

        $todo2 = new Todo();
        $todo2->title = 'Todo 2';
        $todo2->description = 'Test von Todo 1';
        $todo2->dueDate = date("Y-m-d H:i:s");

        //Referenzen zur Fremdschlüssel Tabelle
        $user = User::first();
        $todo2->creator()->associate($user);

        $todo2->save();

        $users2 = User::all()->pluck("id");
        $todo2->users()->sync($users2);

        $tags2 = Tag::all()->pluck("id");
        $todo2->tags()->sync($tags2);
    }

}
