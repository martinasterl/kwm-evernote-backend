<?php

namespace Database\Seeders;

use App\Models\ListModel;
use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $list = new ListModel();
        $list->name = 'Liste 1';
        $list->created_at = date("Y-m-d H:i:s");
        $list->updated_at = date("Y-m-d H:i:s");

        //In die DB speichern
        $list->save();

        $users = User::all()->pluck("id");
        $list->users()->sync($users);

         /*DB::table('lists')->insert([
            'name' => 'Liste 2',
            'created_at' => date("Y-m-d H:i:s"),
            'updated_at' => date("Y-m-d H:i:s")
        ]);*/
    }

}
