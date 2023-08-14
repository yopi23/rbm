<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class OwnerController extends Controller
{
    //
    public function getTable($thead = '<th>No</th><th>Aksi</th>',$tbody = ''){
        $result = '<div class="table-responsive"><table class="table" id="dataTable">';
        $result .= '<thead>'.$thead.'</thead>';
        $result .= '<tbody>'.$tbody.'</body>';
        $result .= '</table></div>';
        return $result;
    }
    public function getHiddenItemForm($method = 'POST'){
        $result = ''.csrf_field().'';
        $result .= ''.method_field($method).'';
        return $result;
    }
    public function getRoleUser($role = '0'){
        switch ($role){
            case '0':
                return "Administrator";
                break;
            case '1':
                return "Owner";
                break;
            case '2':
                return "Kasir";
                break;
            case '3':
                return "Teknisi";
                break;
        }
    }
    public function getStatusUser($status = '0'){
        switch ($status){
            case '0':
                return '<span class="badge badge-danger">Tidak Aktif</span>';
                break;
            case '1':
                return '<span class="badge badge-success">Aktif</span>';
                break;
        }
    }
    public function index(){
        $page = "Owner";
        $thead = '<th width="5%">No</th>
        <th>Nama</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Aksi</th>';
        $data = User::join('user_details','users.id','=','user_details.kode_user')->where('user_details.jabatan','=','1')->get(['users.*','user_details.*','users.id as id_user']);
        $tbody = '';
        $no = 1;
        foreach($data as $item){
            $edit = Request::create(route('owner.edit',$item->id_user));
            $tbody .= '<tr><td>'.$no++.'</td>
                        <td>'.$item->name.'</td>
                        <td>'.$item->email.'</td>
                        <td>'.$this->getRoleUser($item->jabatan).'</td>
                        <td>'.$this->getStatusUser($item->status_user).'</td>
                        <th><a href="'.$edit->url().'" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a></th>';
        }
        $link_tambah = route('owner.create');
        $data = $this->getTable($thead,$tbody);
        return view('admin.layout.card_layout',compact(['page','data','link_tambah']));
    }
    public function create()
    {
        //
        $page = "Tambah Owner";
        return view('admin.forms.owner',compact(['page']));
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
        $validate = $this->validate($request,[
            'name' => ['required'],
            'email' => ['required'],
            'password' => ['required'],
        ]);
        if($validate){
            $create = User::create([
                'name'=> $request->name,
                'email'=> $request->email,
                'password'=> Hash::make($request->password),
            ]);
            if($create){
                $data_user = User::where([['name','=',$request->name],['email','=',$request->email]])->get()->first();
                if($data_user->id != null || !empty($data_user->id)){
                    $kode_invite = 'INV'.$data_user->id.$data_user->jabatan.rand(500,1000);
                    UserDetail::create([
                        'kode_user' => $data_user->id,
                        'foto_user' => '-',
                        'fullname' => $data_user->name,
                        'alamat_user' => '',
                        'no_telp' => '-',
                        'jabatan' => '1',
                        'id_upline' => $data_user->id,
                        'status_user' => $request->status_user,
                        'kode_invite' => $kode_invite,
                        'link_twitter' =>'-', 
                        'link_facebook' => '-', 
                        'link_instagram' =>'-', 
                        'link_linkedin' =>'-',   
                    ]);
                }
                    return redirect()->route('owner.index')
                    ->with([
                        'success' => 'Owner Berhasil Ditambahkan'
                    ]);
                return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $page = "Edit Owner";
        $data = User::join('user_details','user_details.kode_user','=','users.id')->where('users.id','=',$id)->get(['users.*','user_details.*','users.id as id_user'])->first();
        return view('admin.forms.owner',compact(['page','data']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $validate = $this->validate($request,[
            'name' => ['required'],
            'email' => ['required'],
        ]);
        if($validate){
            $data = User::findOrFail($id);
            $pass = $request->password != null ? Hash::make($request->password) : $data->password;
            $data->update([
                'name'=> $request->name,
                'email'=> $request->email,
                'password'=>$pass,
            ]);
            if($data){
                $data_user = UserDetail::where([['kode_user','=',$id]])->get()->first();
                if($data_user->id != null || !empty($data_user->id)){
                    $data_user->update([
                        'fullname' => $request->name,
                        'status_user' => $request->status_user, 
                    ]);
                    $data_bawahan = UserDetail::where([['id_upline','=',$id]])->get();
                    foreach ($data_bawahan as $bawahan) {
                        $bawahan->update([
                            'status_user' => $request->status_user, 
                        ]);
                    }
                    
                }
                    return redirect()->route('owner.index')
                    ->with([
                        'success' => 'Owner Berhasil DiEdit'
                    ]);
                return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

    }
}
