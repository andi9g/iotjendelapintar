<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class IoT extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kendali', function (Blueprint $table) {
            $table->bigIncrements('idkendali');
            $table->string('namakendali')->unique();
            $table->boolean('ket')->default(0);
            $table->timestamps();
        });

        $kendali = [
            'jendela',
            'gorden',
        ];

        foreach ($kendali as $key) {
            DB::table('kendali')->insert([
                'namakendali' => $key,
            ]);
        }

        Schema::create('alat', function (Blueprint $table) {
            $table->bigIncrements('idalat');
            $table->String('token_sensor')->unique();
            $table->timestamps();
        });

        DB::table('alat')->insert([
            'token_sensor' => uniqid()."_".strtotime(date(now())),
        ]);

        Schema::create('data', function (Blueprint $table) {
            $table->bigIncrements('iddata');
            $table->string('namadata')->unique();
            $table->double('nilai')->default(0);
            $table->timestamps();
        });

        Schema::create('logs', function (Blueprint $table) {
            $table->bigIncrements('idlogs');
            $table->string('logs');
            $table->date('tanggal');
            $table->char('jam', 5);
            $table->timestamps();
        });

        Schema::create('status', function (Blueprint $table) {
            $table->bigIncrements('idstatus');
            $table->enum("status", ['otomatis', 'tidak otomatis'])->default("otomatis");
            $table->timestamps();
        });

        Schema::create('jadwal', function (Blueprint $table) {
            $table->bigIncrements('idjadwal');
            $table->char('jambuka', 5);
            $table->char('jamtutup', 5);
            $table->string('email');
            $table->integer('menit')->default(1);
            $table->timestamps();
        });

        DB::table('jadwal')->insert([
            'jambuka'=> "06:00",
            'jamtutup'=> "05:00",
            'email'=> "andirizky.bayuputra@gmail.com",
        ]);

        DB::table("status")->insert([
            'status' => 'otomatis',
        ]);

        $data = [
            'jarakjendela',
            'jarakgorden',
            'raindrops',
            'ket'
        ];

        foreach ($data as $key) {
            DB::table('data')->insert([
                'namadata' => $key,
            ]);
        }

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
