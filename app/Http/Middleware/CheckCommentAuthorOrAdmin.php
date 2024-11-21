<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Helpers\ResponseJson;

class CheckCommentAuthorOrAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $commentId = $request->route('commentId');
        $comment = Comment::find($commentId);

        if (!$comment) {
            return ResponseJson::pageNotFoundResponse('Comment not found', []);
        }

        if ($comment->user_id !== $user->id && $user->role !== 'admin') {
            return ResponseJson::forbidenResponse('Unauthorized to edit or delete this comment.', []);
        }

        return $next($request);
    }
}
