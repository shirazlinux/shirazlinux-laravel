<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\Seo;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->postsOnly()
            ->with('author')
            ->orderByDesc('published_at')
            ->limit(50)
            ->get();

        $siteTitle = setting('site_name', 'شیرازلینوکس');
        $siteLink = url('/');
        $siteDesc = Seo::description(
            setting('seo_default_description', setting('site_description', 'جامعه نرم‌افزار آزاد شیراز')),
            280
        );

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml[] = '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">';
        $xml[] = '<channel>';
        $xml[] = '<title>'.e($siteTitle).'</title>';
        $xml[] = '<link>'.e($siteLink).'</link>';
        $xml[] = '<description>'.e($siteDesc).'</description>';
        $xml[] = '<language>fa-IR</language>';
        $xml[] = '<lastBuildDate>'.now()->toRfc2822String().'</lastBuildDate>';
        $xml[] = '<ttl>60</ttl>';
        $xml[] = '<atom:link href="'.e(url('/feed.xml')).'" rel="self" type="application/rss+xml"/>';
        $xml[] = '<image>';
        $xml[] = '<url>'.e(Seo::absolute(setting('seo_organization_logo', 'media/website/icon.png'))).'</url>';
        $xml[] = '<title>'.e($siteTitle).'</title>';
        $xml[] = '<link>'.e($siteLink).'</link>';
        $xml[] = '</image>';

        foreach ($posts as $post) {
            $link = url('/'.$post->slug);
            $title = trim(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', (string) ($post->title ?: $post->slug)) ?? '');
            $desc = Seo::description($post->excerpt ?: strip_tags((string) $post->body), 320);
            $pub = optional($post->published_at)->toRfc2822String() ?: now()->toRfc2822String();
            $author = $post->author?->name;

            $xml[] = '<item>';
            $xml[] = '<title>'.e($title).'</title>';
            $xml[] = '<link>'.e($link).'</link>';
            $xml[] = '<guid isPermaLink="true">'.e($link).'</guid>';
            $xml[] = '<pubDate>'.e($pub).'</pubDate>';
            if ($author) {
                $xml[] = '<dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">'.e($author).'</dc:creator>';
            }
            $xml[] = '<description><![CDATA['.$desc.']]></description>';
            if ($post->featured_image) {
                $img = Seo::absolute($post->featured_image);
                $xml[] = '<media:content url="'.e($img).'" medium="image"/>';
                $xml[] = '<enclosure url="'.e($img).'" type="image/jpeg"/>';
            }
            $xml[] = '</item>';
        }

        $xml[] = '</channel>';
        $xml[] = '</rss>';

        return response(implode("\n", $xml)."\n", 200)
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=1800');
    }
}
