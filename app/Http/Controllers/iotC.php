<?php

namespace App\Http\Controllers;

use App\Models\dataM;
use App\Models\kendaliM;
use App\Models\logsM;
use App\Models\statusM;
use App\Models\User;
use App\Models\alatM;
use App\Models\jadwalM;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Hash;
use Auth;
use Illuminate\Http\Request;

class iotC extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function login(Request $request)
    {
        try {
            $username = $request->username;
            $password = $request->password;
            
            $response = [
                'pesan' => "username dan password salah",
                'login' => false,
            ];

            $data = User::where('username', $username);
            
            if($data->count() === 1) {
                if(Hash::check($password, $data->first()->password)){
                    
                    $alat = alatM::latest()->first();

                    $response = [
                        'nama' => $data->first()->name,
                        'username' => $data->first()->username,
                        'email' => $data->first()->email,
                        'login' => true,
                        'pesan' => "Welcome",
                        'token_sensor' => $alat->token_sensor,
                    ];

                    
                }
            }

            return $response;

        } catch (\Throwable $th) {
            $response = [
                'pesan' => "username dan password salah",
                'login' => false,
            ];

            return $response;
        }
    }
     

    public function status(Request $request, $token_sensor)
    {            
        $cek = alatM::where('token_sensor', $token_sensor)->count();
        
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }

        $status = statusM::select("status")->first();

        return $status;
    }

    public function coba(Request $request)
    {
    
    }
    public function kendaliStatus(Request $request, $token_sensor)
    {    
        $cek = alatM::where('token_sensor', $token_sensor)->count();
        
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }

        $status = statusM::select("status","idstatus")->first();


        if($status->status == "otomatis"){
            $data = "tidak otomatis";
        }else {
            $data = "otomatis";
        }
        
        $status->update([
            'status' => $data,
        ]);

        $status = statusM::select("status")->first();
        
        return $status;
    }


    public function kendali(Request $request, $token_sensor, $ket)
    {

        try {
            $cek = alatM::where('token_sensor', $token_sensor)->count();

            if($cek === 1) {
                $kendaliJendela = kendaliM::where('namakendali', 'jendela')->first();
                $kendaliGorden = kendaliM::where('namakendali', 'gorden')->first();

                if($ket == 'jendela') {
                    if($kendaliJendela->ket == 0) {
                        $data = 1;
                    }else {
                        $data = 0;
                    }

                    $kendaliJendela->update([
                        'ket' => $data,
                    ]);



                }elseif($ket == 'gorden') {
                    if($kendaliGorden->ket == 0) {
                        $data = 1;
                    }else {
                        $data = 0;
                    }

                    $kendaliGorden->update([
                        'ket' => $data,
                    ]);
                }

                $status = statusM::select("status","idstatus")->first();
                $ket = "tidak otomatis";
                $status->update([
                    'status'=> $ket,
                ]);


                $response = [
                    'pesan' => "success",
                ];

                return $response;

            }
        } catch (\Throwable $th) {
            $response = [
                'pesan' => "terjadi kesalahan",
            ];
            return $response;
        }
        


        
    }

    
     

    public function post(Request $request)
    {
        try {
            $token_sensor = $request->header('token-sensor');
            
            $cek = alatM::where('token_sensor', $token_sensor)->count();
            
            if($cek === 0 ){
                return abort(500, 'Kunci tidak valid');
            }
            
            $jsonData = $request->getContent();
            $json = json_decode($jsonData, true);
            $json = $json[0];
            $logs = "";

            $jarakgorden = (int)$json['jarakgorden'];
            $jarakjendela = (int)$json['jarakjendela'];
            $raindrops = (int)$json['raindrops'];
            $waktu = $json['waktu'];
            $logsWaktu = date("d/m/Y H:i", $waktu);
            
            
            $jadwal = jadwalM::first();
            $email_jadwal = $jadwal->email;
            $jambuka = strtotime(date('Y-m-d')." ".$jadwal->jambuka);
            $jamtutup = strtotime(date('Y-m-d')." ".$jadwal->jamtutup);
            
            $jam = date("G", $waktu);
            $jambuka = date("G", $jambuka);
            $jamtutup = date("G", $jamtutup);

            $otomatis = statusM::first();
            
            $jendela = kendaliM::where('namakendali', 'jendela')->first()->ket;
            $gorden = kendaliM::where('namakendali', 'jendela')->first()->ket;
            $buzzer = 0;
            $ket = 0;
            $title = "Notifikasi Jendela IoT";


            if($otomatis->status == "otomatis") {
                
                if($raindrops < 400) {
                    $ket = 1;
                    
                }else if($raindrops < 430) {
                    $ket = 2;
                }else {
                    $ket = 3;
                }


                if(($jam <= $jamtutup) && ($jam>=$jambuka)){
                    if($ket == 1) {
                        $jendela = 0;
                        $gorden = 0;
                        $buzzer = 1;
                        $content =  "Kodisi Cuaca   : Hujan, \n".
                                    "Jadwal         : Buka, \n".
                                    "Jendela        : ".(($jarakjendela==0)?"Tutup":"Buka")."\n".
                                    "Tirai          : ".(($jarakgorden==0)?"Tutup":"Buka")."\n";
                    }else if($ket == 2) {
                        $buzzer = 2;
                        $content =  "Kodisi Cuaca   : Waspada Hujan, \n".
                                    "Jadwal         : Buka, \n".
                                    "Jendela        : ".(($jarakjendela==0)?"Tutup":"Buka")."\n".
                                    "Tirai          : ".(($jarakgorden==0)?"Tutup":"Buka")."\n";
                    }else if($ket == 3) {
                        $jendela = 1;
                        $gorden = 1;
                        $content =  "Kodisi Cuaca   : Tidak Hujan, \n".
                                    "Jadwal         : Buka, \n".
                                    "Jendela        : ".(($jarakjendela==0)?"Tutup":"Buka")."\n".
                                    "Tirai          : ".(($jarakgorden==0)?"Tutup":"Buka")."\n";
                    }
                }else {
                    $jendela = 0;
                    $gorden = 0;
                    $ket = 0;
                    $content =  "Kodisi Cuaca   : Tidak Membaca Data, \n".
                                "Jadwal         : Jadwal Tutup, \n".
                                "Jendela        : ".(($jarakjendela==0)?"Tutup":"Buka")."\n".
                                "Tirai          : ".(($jarakgorden==0)?"Tutup":"Buka")."\n";
                }

                dataM::where('namadata', 'jarakjendela')->update([
                    'nilai' => $jarakjendela,
                ]);

                dataM::where('namadata', 'jarakgorden')->update([
                    'nilai' => $jarakgorden,
                ]);
                dataM::where('namadata', 'raindrops')->update([
                    'nilai' => $raindrops,
                ]);
                dataM::where('namadata', 'ket')->update([
                    'nilai' => $ket,
                ]);

                kendaliM::where('namakendali', 'jendela')->update([
                    'ket' => $jendela,
                ]);
                kendaliM::where('namakendali', 'gorden')->update([
                    'ket' => $gorden,
                ]);

                $cek = logsM::orderBy('idlogs', 'desc');
                $logs = "Jarak Tirai: ".$jarakgorden."|Jarak Jendela: ".$jarakjendela."|Raindrops: ".$raindrops."|".$logsWaktu;
            
                if($cek->count() === 0) {
                    logsM::insert([
                        'logs' => $logs,
                        'tanggal' => date("Y-m-d", $waktu),
                        'jam' => date("H:i", $waktu),
                    ]);
                }else {
                    
                    $cek = $cek->first();
                    $waktu2 = strtotime(date("Y-m-d H:i:s", $waktu));
                    $tanggalcek = date("Y-m-d H:i:s",strtotime($cek->tanggal." ".$cek->jam.":00"));
                    $tambahmenit = strtotime("+".$jadwal->menit." minutes", strtotime($tanggalcek));
                    
                    if($waktu2 > $tambahmenit) {
                        
                
                        

                        logsM::insert([
                            'logs' => $logs,
                            'tanggal' => date("Y-m-d", $waktu),
                            'jam' => date("H:i", $waktu),
                        ]);

                        $mailData = [
                            'title' => $title,
                            'content' => $content,
                        ];
                
                        $kirim = Mail::raw($mailData['content'], function (Message $message) use ($mailData, $email_jadwal) {
                            $message->to($email_jadwal)
                                    ->subject($mailData['title']);
                        });
                    }
                }
                

                $pesan = [
                    "buzzer" => $buzzer,
                ];

                return $pesan;
                       
            }else {
                dataM::where('namadata', 'jarakjendela')->update([
                    'nilai' => $jarakjendela,
                ]);

                dataM::where('namadata', 'jarakgorden')->update([
                    'nilai' => $jarakgorden,
                ]);
                dataM::where('namadata', 'raindrops')->update([
                    'nilai' => $raindrops,
                ]);
                dataM::where('namadata', 'ket')->update([
                    'nilai' => $ket,
                ]);
                $pesan = [
                    "buzzer" => $buzzer,
                ];

                return $pesan;
            }

        } catch (\Throwable $th) {
            return abort(500, 'Kunci tidak valid');
        }
        

    }

    public function pengaturan(Request $request, $token_sensor)
    {
        $data = jadwalM::first();
        return $data;
    }

    public function editPengaturan(Request $request,$token_sensor)
    {
        $cek = alatM::where('token_sensor', $token_sensor)->count();
            
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }
        $req = $request->all();
        $data = jadwalM::first();
        $data->update($req);

        if($data) {
            $pesan = [
                "pesan" => "Success",
            ];
        }else {
            $pesan = [
                "pesan" => "Terjadi kesalahan",
            ];
        }
        return $pesan;
    }



    public function logs(Request $request)
    { 
        $logs = logsM::orderBy('idlogs', 'desc')->select("logs", "tanggal", "jam")->take(30)->get();
        $i = 0;
        $nilai = array();
        foreach ($logs as $val) {
            $ex = explode("|",$val->logs);

            $nilai[$i]["tirai"] = $ex[0];
            $nilai[$i]["jendela"] = $ex[1];
            $nilai[$i]["raindrops"] = $ex[2];
            $nilai[$i]["tanggal"] = $ex[3];

            $i++;
        }
        return $nilai;
    }

    
    
    
    public function data()
    {
        $data = dataM::select('namadata', 'nilai' )->get();
        
        return $data;
    }


    public function dataKendali(Request $request, $token_sensor)
    {
        $cek = alatM::where('token_sensor', $token_sensor)->count();
            
        if($cek === 0 ){
            return abort(500, 'Kunci tidak valid');
        }
        $data = kendaliM::get();
        $status = statusM::first()->status;
        foreach ($data as $key) {
            $nilai[$key->namakendali] = $key->ket;
        }
        $nilai['status'] = $status;
        
        return $nilai;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function show(banjirM $banjirM)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function edit(banjirM $banjirM)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, banjirM $banjirM)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\banjirM  $banjirM
     * @return \Illuminate\Http\Response
     */
    public function destroy(banjirM $banjirM)
    {
        //
    }
}
