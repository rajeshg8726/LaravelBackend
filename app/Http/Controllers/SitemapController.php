<?php
// filepath: app/Http/Controllers/SitemapController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Job; // Assuming you have a Job model

class SitemapController extends Controller
{
    public function generateSitemap()
    {
        // Fetch all jobs from the database
        $jobs = Job::all();

        // Create the XML structure
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Add static pages
        $sitemap .= '
            <url>
                <loc>' . url('/') . '</loc>
                <lastmod>' . now()->toDateString() . '</lastmod>
                <changefreq>daily</changefreq>
                <priority>1.0</priority>
            </url>
            <url>
                <loc>' . url('/about') . '</loc>
                <lastmod>' . now()->toDateString() . '</lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.8</priority>
            </url>
            <url>
                <loc>' . url('/contact') . '</loc>
                <lastmod>' . now()->toDateString() . '</lastmod>
                <changefreq>monthly</changefreq>
                <priority>0.8</priority>
            </url>
            <url>
                <loc>' . url('/updates') . '</loc>
                <lastmod>' . now()->toDateString() . '</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.7</priority>
            </url>
        ';

        // Add dynamic job URLs
        foreach ($jobs as $job) {
            $sitemap .= '
                <url>
                    <loc>' . url('/job/' . $job->id . '-' . \Str::slug($job->title)) . '</loc>
                    <lastmod>' . $job->updated_at->toDateString() . '</lastmod>
                    <changefreq>daily</changefreq>
                    <priority>0.9</priority>
                </url>
            ';
        }

        $sitemap .= '</urlset>';

        // Return the sitemap as an XML response
        return response($sitemap, 200)
            ->header('Content-Type', 'application/xml');
    }
}