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
        try{
            $comment = Comment::where('id', $commentId)->where('user_id', $userId)->first();

            if (!$comment) {
                return [false, 'Comment not found or unauthorized.', []];
            }

            $comment->update(['content' => $data['content']]);

            return [true, 'Comment updated successfully.', $comment];
        } catch (\Exception $e){
            Log::error($e->getMessage());
            return [false, 'Failed to edit comment.', []];
        }
    }
    public function deleteComment(int $commentId, int $userId): array
    {
        try{
            $comment = Comment::where('id', $commentId)->where('user_id', $userId)->first();

            if (!$comment) {
                return [false, 'Comment not found or unauthorized.', []];
            }

            // Soft delete and mark as deleted
            $comment->update(['status' => 'deleted', 'content' => null]);
            $comment->delete();

            return [true, 'Comment deleted successfully.', []];
        } catch (\Exception $e){
            Log::error($e->getMessage());
            return [false, 'Failed to delete comment.', []];
        }
    }


    public function getComments(int $diseaseId): array
    {
        $comments = Comment::with([
            'replies' => function ($query) {
                $query->withTrashed(); // Include soft-deleted replies
            },
            'user:id,name,email,role', // Include user details for parent comments
            'replies.user:id,name,email,role' // Include user details for replies
        ])
        ->where('disease_id', $diseaseId)
        ->whereNull('parent_id') // Fetch only parent comments
        ->withTrashed() // Include soft-deleted parents
        ->get()
        ->map(function ($comment) {
            return [
                'id' => $comment->id,
                'content' => $comment->content_with_placeholder, // Use placeholder content for deleted comments
                'status' => $comment->status,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'user' => $comment->user ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email,
                    'role' => $comment->user->role
                ] : null,
                'replies' => $comment->replies->map(function ($reply) {
                    return [
                        'id' => $reply->id,
                        'content' => $reply->content_with_placeholder,
                        'status' => $reply->status,
                        'created_at' => $reply->created_at,
                        'updated_at' => $reply->updated_at,
                        'user' => $reply->user ? [
                            'id' => $reply->user->id,
                            'name' => $reply->user->name,
                            'email' => $reply->user->email,
                            'role' => $reply->user->role
                        ] : null
                    ];
                })->toArray()
            ];
        })->toArray(); // Convert to array for the response

        return [true, 'Comments retrieved successfully.', $comments];
    }

}
