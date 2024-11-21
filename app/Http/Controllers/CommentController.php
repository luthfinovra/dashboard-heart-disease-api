<?php

namespace App\Http\Controllers;

use App\Services\CommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Helpers\ResponseJson;
use App\Http\Requests\CreateCommentRequest;
use App\Http\Requests\DeleteCommentRequest;
use App\Http\Requests\EditCommentRequest;

class CommentController extends Controller
{
    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    public function createComment(CreateCommentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $userId = $request->user()->id;

        [$success, $message, $comment] = $this->commentService->createComment($data, $userId);

        if (!$success) {
            return ResponseJson::failedResponse($message, $comment);
        }

        return ResponseJson::successResponse($message, $comment);
    }

    public function editComment(EditCommentRequest $request, $diseaseId, $commentId): JsonResponse
    {
        $data = $request->validated();

        $userId = $request->user()->id;

        [$success, $message, $comment] = $this->commentService->editComment($commentId, $data, $userId);

        if (!$success) {
            return ResponseJson::failedResponse($message, $comment);
        }

        return ResponseJson::successResponse($message, $comment);
    }

    public function deleteComment(DeleteCommentRequest $request, $diseaseId, $commentId): JsonResponse
    {
        $userId = request()->user()->id;

        [$success, $message, $comment] = $this->commentService->deleteComment($commentId, $userId);

        if (!$success) {
            return ResponseJson::failedResponse($message, $comment);
        }

        return ResponseJson::successResponse($message, $comment);
    }

    public function getComments($diseaseId): JsonResponse
    {
        [$success, $message, $comments] = $this->commentService->getComments($diseaseId);

        if (!$success) {
            return ResponseJson::failedResponse($message, $comments);
        }

        return ResponseJson::successResponse($message, $comments);
    }
}
