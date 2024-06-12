<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class AdviceController extends Controller
{
    public function showForm()
    {
        return view('advice.form');
    }

    public function getAdvice(Request $request)
    {
        // Получение данных из запроса
        $age = $request->input('age');
        $weight = $request->input('weight');
        $height = $request->input('height');
        $gender = $request->input('gender');
        $fitness_level = $request->input('fitness_level');
        $goal = $request->input('goal');

        // Отправка запроса к Flask API
        $client = new Client();
        $response = $client->request('GET', 'http://127.0.0.1:5001/get_advice', [
            'query' => [
                'age' => $age,
                'weight' => $weight,
                'height' => $height,
                'gender' => $gender,
                'fitness_level' => $fitness_level,
                'goal' => $goal
            ]
        ]);

        // Получение ответа от Flask API
        $data = json_decode($response->getBody(), true);

        return response()->json($data);
    }
}
