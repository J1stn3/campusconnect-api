<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    use ApiResponseTrait;

    public function chat(Request $request): JsonResponse
    {
        return $this->successResponse(
            null,
            'Chatbot endpoint placeholder. Coming in a later module.'
        );
    }
}
