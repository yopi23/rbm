<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        User::factory()->create([
            'id' => '1',
            'name' => 'Administrator',
            'email' => 'admin@mail.com',
            'password' => Hash::make('admin'),
        ]);
        UserDetail::insert([
            'kode_user'=> '1',
            'foto_user'=> '-',
            'fullname'=> 'Administrator',
            'alamat_user'=> '-',
            'no_telp'=> '',
            'jabatan'=> '0',
            'status_user'=> '1',
            'kode_invite'=> '-',
            'link_twitter'=> 'https://twitter.com/',
            'link_facebook'=> 'https://web.facebook.com/',
            'link_instagram'=> 'https://www.instagram.com/',
            'link_linkedin'=> 'https://www.linkedin.com/', 
        ]);
    }
}
