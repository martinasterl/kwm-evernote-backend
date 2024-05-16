<?php

namespace Database\Seeders;

use App\Models\ListModel;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Termwind\Components\Li;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new User();
        $user->username = "paullang";
        $user->firstName = 'Paul';
        $user->lastName = 'Lang';
        $user->password = bcrypt('passwort');
        $user->image = 'https://picsum.photos/200/300';

        $user->save();

        $lists = ListModel::all()->pluck("id");
        $user->lists()->sync($lists);

        $todos = Todo::all()->pluck("id");
        $user->todos()->sync($todos);

        $user2 = new User();
        $user2->username = "annamaier";
        $user2->firstName = 'Anna';
        $user2->lastName = 'Maier';
        $user2->password = bcrypt('passwort');
        $user2->image = 'https://picsum.photos/200/300';

        $user2->save();

        $lists2 = ListModel::all()->pluck("id");
        $user2->lists()->sync($lists2);

        $todos2 = Todo::all()->pluck("id");
        $user2->todos()->sync($todos2);

    }

}
