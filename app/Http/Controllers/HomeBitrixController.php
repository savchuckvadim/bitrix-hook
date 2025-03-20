<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeBitrixController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $data = ['initialData' => 'bitrix'];
   
        return view('bitrix', $data);
    }

    public function post(Request $request)
    {
        $customData = [
            // 'user' => $user ? ['id' => $user->id, 'name' => $user->name] : null,
            'request_data' => $request->all(), // Передаём request-данные во фронт
        ];

        return view('bitrix', ['initialData' => json_encode($customData)]);
    }
}
