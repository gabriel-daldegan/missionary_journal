<?php

namespace App\Http\Controllers;

use App\Models\BlogPostCategory;
use App\Services\BlogService;

class BlogController extends Controller
{
    public function __construct(
        private BlogService $blogService
    ) {}

    public function view(string $slug)
    {
        $user = auth()->user();
        $isPublished = $user && $user->isAdmin() ? null : true; // if user is admin, show all posts, otherwise only published posts

        $post = $this->blogService->getBlogBySlug($slug, $isPublished);

        return view('blog.view', [
            'post' => $post,
            'morePosts' => $this->blogService->getMorePosts($post),
        ]);
    }

    public function all()
    {
        return view('blog.all', [
            'posts' => $this->blogService->getAllPosts(),
        ]);
    }

    public function category(string $slug)
    {
        $category = BlogPostCategory::where('slug', $slug)->firstOrFail();

        return view('blog.category', [
            'category' => $category,
            'posts' => $this->blogService->getAllPostsForCategory($category),
        ]);
    }
}
