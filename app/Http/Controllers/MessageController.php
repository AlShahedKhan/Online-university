<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;

class MessageController extends Controller
{
    use HandlesApiResponse;

    public function store(Request $request)
    {
        return $this->safeCall(function () use ($request) {
            // Validate request
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'message' => 'required|string',
            ]);

            // Store message
            $message = Message::create($validatedData);

            return $this->successResponse('Message sent successfully!', $message->toArray());
        });
    }

    /**
     * Display a listing of messages.
     */
    public function index()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $messages = Message::latest()->get();
            return $this->successResponse('Messages retrieved successfully.', ['messages' => $messages]);
        });
    }
}
