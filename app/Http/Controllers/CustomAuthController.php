<?php

namespace App\Http\Controllers;
use App\Models\User;
use Hash;
use Illuminate\Support\Facades\Session;

use Illuminate\Http\Request;

class CustomAuthController extends Controller
{
    public function login(){
        return view('auth.login');
    }
    public function registration(){
        return view('auth.registration');
    }
    public function registerUser(request $request){
        $request -> validate(
            [
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:5|max:12',
                'confirm_password' => 'required|min:5|max:12|same:password'
            ]
        );
        //echo('registered user');
        $user = new User();
        $user -> name = $request -> name;
        $user -> email = $request -> email;
        $user -> password = Hash::make($request -> password);
        $res = $user -> save();
        if($res){
            return back() -> with('success', 'You have registered successfully');
        }else{
            return back() -> with('fail', 'Something went wrong, try again later');
        }
    }
    public function loginUser(Request $request){
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:5|max:12',
        ]);

        // Check user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('fail', 'You are not a registered user!');
        } else {
            // Check password
            if (Hash::check($request->password, $user->password)) {
                $request->session() -> put('loginId', $user->id);   
                return redirect('/dashboard')->with('success', 'Login successful');
            } else {
                return back()->with('fail', 'Incorrect password');
            }
        }
    }

    public function dashboard(){
        $data = array();
        if( Session::has('loginId') ){
            $data = User::where('id', Session::get('loginId'))->first();
        }
        return view('dashboard',compact('data'));
    }

    public function logout(){
        if(Session::has('loginId')){
            Session::pull('loginId');
            return redirect('/login');
        }
    }
}
