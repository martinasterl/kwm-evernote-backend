<?php

namespace Database\Seeders;

use App\Models\Note;
use App\Models\Tag;
use App\Models\Todo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tag = new Tag();

        $todos = Todo::all()->pluck("id");
        $tag->todos()->sync($todos);

        $notes = Note::all()->pluck("id");
        $tag->notes()->sync($notes);
    }
}
