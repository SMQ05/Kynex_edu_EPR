<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CmsAnnouncement;
use App\Models\Tenant\CmsGalleryAlbum;
use App\Models\Tenant\CmsPage;
use App\Models\Tenant\CmsSetting;
use App\Models\Tenant\CmsSlider;
use App\Models\Tenant\ExamResult;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    protected function settings(): CmsSetting
    {
        return CmsSetting::getSettings();
    }

    public function home(): View
    {
        return view('cms.home', [
            'settings'      => $this->settings(),
            'sliders'       => CmsSlider::active()->get(),
            'announcements' => CmsAnnouncement::active()->latest('published_at')->take(5)->get(),
        ]);
    }

    public function about(): View
    {
        return view('cms.about', [
            'settings' => $this->settings(),
        ]);
    }

    public function admissions(): View
    {
        return view('cms.admissions', [
            'settings' => $this->settings(),
        ]);
    }

    public function gallery(): View
    {
        return view('cms.gallery', [
            'settings' => $this->settings(),
            'albums'   => CmsGalleryAlbum::published()->with('photos')->get(),
        ]);
    }

    public function news(): View
    {
        return view('cms.news', [
            'settings'      => $this->settings(),
            'announcements' => CmsAnnouncement::active()
                ->latest('published_at')
                ->paginate(12),
        ]);
    }

    public function contact(): View
    {
        return view('cms.contact', [
            'settings' => $this->settings(),
        ]);
    }

    public function contactSubmit(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        // Store in communication log or send email
        // For now, redirect back with success
        return back()->with('success', 'Thank you for contacting us. We will get back to you shortly.');
    }

    public function results(Request $request): View
    {
        $results = null;

        if ($request->filled('admission_number')) {
            $results = ExamResult::query()
                ->whereHas('student', fn ($q) => $q->where('admission_number', $request->admission_number))
                ->whereHas('exam', fn ($q) => $q->where('publish_results', true))
                ->with(['exam', 'student.schoolClass', 'student.section'])
                ->latest()
                ->get();
        }

        return view('cms.results', [
            'settings' => $this->settings(),
            'results'  => $results,
            'query'    => $request->admission_number,
        ]);
    }

    public function page(string $slug): View
    {
        $page = CmsPage::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('cms.page', [
            'settings' => $this->settings(),
            'page'     => $page,
        ]);
    }
}
