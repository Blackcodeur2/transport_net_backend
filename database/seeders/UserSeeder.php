<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'nom' => 'Black',
            'prenom' => 'Codeur',
            'email' => 'blackcodeur@gmail.com',
            'matricule' => '',
            'date_naissance' => date('Y-m-d'),
            'region' => 'Ouest',
            'ville' => 'Dschang',
            'pos_lat' => 10.5,
            'pos_lng' => 5.2,
            'telephone' => '654827481',
            'role_user' => 'ADMIN',
            'password' => Hash::make('Nguefack17232@'),
        ]);
        /*User::create([
            'nom' => 'Amougou',
            'prenom' => 'Jean',
            'email' => 'amougou@gmail.com',
            'matricule' => 'user_002_cm',
            'date_naissance' => date('Y-m-d'),
            'region' => 'Centre',
            'ville' => 'Yaounde',
            'pos_lat' => 8.5,
            'pos_lng' => 7.2,
            'telephone' => '699854712',
            'role_user' => 'PROPRIETAIRE',
            'password' => Hash::make('Nguefack17232@'),
        ]);
        User::create([
            'nom' => 'Ngoufack',
            'prenom' => 'Pierre',
            'email' => 'ngoufack@gmail.com',
            'matricule' => 'user_003_cm',
            'date_naissance' => date('Y-m-d'),
            'region' => 'Est',
            'ville' => 'Bertoua',
            'pos_lat' => 7.5,
            'pos_lng' => 7.2,
            'telephone' => '697511455',
            'role_user' => 'PROPRIETAIRE',
            'password' => Hash::make('Nguefack17232@'),
        ]);*/

        //users aleatoires
        //User::factory()->count(10)->create();
        // Prorietaires
        //User::factory()->count(3)->create(['role_user' => 'PROPRIETAIRE']);

        // chef agence
        //User::factory()->count(3)->create(['role_user' => 'CHEF_AGENCE']);

        // Agents
        //User::factory()->count(4)->create(['role_user' => 'AGENT']);
    }
}
