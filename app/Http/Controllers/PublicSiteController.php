<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Cms\PublicController;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Thin wrapper that exposes the per-tenant CMS website at
 * /site/{tenant}/... on the central domain. Initializes tenancy from the
 * URL parameter, then forwards to the existing Cms\PublicController so
 * we keep one source of truth for the website logic.
 */
class PublicSiteController extends Controller
{
    public function __construct(
        private readonly PublicController $cms,
    ) {}

    protected function bootTenant(string $tenantId): void
    {
        if (tenancy()->initialized) {
            return;
        }

        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            abort(404, 'School not found.');
        }

        tenancy()->initialize($tenant);
    }

    public function home(string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->home();
    }

    public function about(string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->about();
    }

    public function admissions(string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->admissions();
    }

    public function gallery(string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->gallery();
    }

    public function news(string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->news();
    }

    public function newsShow(string $tenant, string $slug)
    {
        $this->bootTenant($tenant);
        return $this->cms->newsShow($slug);
    }

    public function contact(string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->contact();
    }

    public function contactForm(Request $request, string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->contactForm($request);
    }

    public function results(Request $request, string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->results($request);
    }

    public function resultsSearch(Request $request, string $tenant)
    {
        $this->bootTenant($tenant);
        return $this->cms->resultsSearch($request);
    }

    public function page(string $tenant, string $slug)
    {
        $this->bootTenant($tenant);
        return $this->cms->page($slug);
    }
}
