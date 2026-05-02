<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\BlogPostCategory;

class BlogService
{
    public function getBlogBySlug(string $slug, ?bool $isPublished = true)
    {
        $post = BlogPost::where('slug', $slug)->with([
            'author',
            'blogPostCategory',
            'user',
            'media',
        ]);

        if ($isPublished) {
            $post->where('is_published', true);
        }

        return $post->firstOrFail();
    }

    public function getMorePosts(BlogPost $post, int $limit = 3)
    {
        return BlogPost::where('id', '!=', $post->id)
            ->where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->with([
                'author',
                'blogPostCategory',
                'user',
            ])
            ->limit($limit)
            ->get();
    }

    public function getAllPosts(int $limit = 31)
    {
        return $this->getAllPostsQuery()
            ->paginate($limit);
    }

    public function getAllPostsForCategory(BlogPostCategory $category, int $limit = 31)
    {
        return $this->getAllPostsQuery()
            ->where('blog_post_category_id', $category->id)
            ->paginate($limit);
    }

    public function getAllPostsQuery()
    {
        return BlogPost::where('is_published', true)
            ->with([
                'author',
                'blogPostCategory',
                'user',
                'media',
            ])
            ->orderBy('published_at', 'desc');

    }
}
