<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ChatBotController extends Controller
{
    public function index()
    {
        return view('chatbot');
    }

    public function getAnswer(Request $request)
    {
        $question = $request->input('question');

        $client = new Client();
        $response = $client->get('http://127.0.0.1:5000/get_answer', [
            'query' => ['question' => $question]
        ]);

        $body = $response->getBody();
        $data = json_decode($body, true);

        return response()->json($data);
    }

}
