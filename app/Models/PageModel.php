<?php

namespace App\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table         = 'pages';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'title', 'content', 'layout',
        'meta_title', 'meta_desc', 'og_image', 'sort_order', 'status',
    ];
    protected $afterInsert = ['clearSitemapCache'];
    protected $afterUpdate = ['clearSitemapCache'];
    protected $afterDelete = ['clearSitemapCache'];

    public function getBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('status', 'published')->first();
    }

    public function getPublished(): array
    {
        return $this->where('status', 'published')->orderBy('sort_order')->findAll();
    }

    protected function clearSitemapCache(array $data): array
    {
        cache()->delete('seo_sitemap');
        cache()->delete('seo_llms');

        return $data;
    }
}
