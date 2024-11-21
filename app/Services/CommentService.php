<?php

namespace App\Services;

use App\Models\Comment;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function createComment(array $data, int $userId): array
    {
        try {
            $comment = Comment::create([
                'disease_id' => $data['diseaseId'],
                'user_id' => $userId,
                'content' => $data['content'],
                'parent_id' => $data['parent_id'] ?? null,
            ]);
            return [true, 'Comment created successfully.', $comment];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return [false, 'Failed to create comment.', []];
        }
    }

    public function editComment(int $commentId, array $data, int $userId): array
    {
        $comment = Comment::where('id', $commentId)->where('user_id', $userId)->first();

        if (!$comment) {
            return [false, 'Comment not found or unauthorized.', []];
        }

        $comment->update(['content' => $data['content']]);

        return [true, 'Comment updated successfully.', $comment];
    }

    public function deleteComment(int $commentId, int $userId): array
    {
        $comment = Comment::where('id', $commentId)->where('user_id', $userId)->first();

        if (!$comment) {
            return [false, 'Comment not found or unauthorized.', []];
        }

        $comment->delete();

        return [true, 'Comment deleted successfully.', []];
    }

    public function getComments(int $diseaseId): array
    {
        $comments = Comment::where('disease_id', $diseaseId)
            ->whereNull('parent_id')
            ->with('replies')
            ->get();

        return [true, 'Comments retrieved successfully.', $comments];
    }
}
