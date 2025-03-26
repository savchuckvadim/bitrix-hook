<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
        // $data = ['initialData' => 'bitrix', 'next' => 'key'];
   
        // return view('bitrix', $data);
        try {
            $response = Http::get('http://localhost:3002'); // или другой нужный route
            return response($response->body(), 200)
                ->header('Content-Type', 'text/html');
        } catch (\Exception $e) {
            // Log::error('Placement Error: ' . $e->getMessage());
            return response('<h1>500 Ошибка</h1>', 500);
        }
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
