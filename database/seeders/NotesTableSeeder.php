<?php

namespace Database\Seeders;

use App\Models\ListModel;
use App\Models\Note;
use App\Models\Tag;
use App\Models\Todo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $note = new Note();
        $note->title = 'Notiz 1';
        $note->description = 'Test von Todo 1';
        $note->list_id = 1;

        $list = ListModel::first();
        $note->list()->associate($list);

        $note->save();

        $tags = Tag::all()->pluck("id");
        $note->tags()->sync($tags);
    }

}
