<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
class QuoteRequestController extends Controller
{
    public function index(Request $request): Response {
        $status = $request->query('status');
        $search = trim((string) $request->query('search', ''));
        $query = QuoteRequest::with(['user:id,name,email','service:id,name','currency:id,code,symbol'])
            ->withCount('documents')->latest();
        if (in_array($status, ['pending','under_review','quote_draft','quote_sent','quote_sent'], true)) $query->where('status', $status);
        if ($search !== '') $query->where(function ($q) use ($search) {
            $q->where('id', is_numeric($search) ? (int) $search : -1)
                ->orWhereHas('user', fn ($u) => $u->where('name','like',"%{$search}%")->orWhere('email','like',"%{$search}%"))
                ->orWhereHas('service', fn ($s) => $s->where('name','like',"%{$search}%"));
        });
        return Inertia::render('admin/quote-requests/index', [
            'quoteRequests' => $query->paginate(15)->withQueryString(),
            'filters' => ['status' => in_array($status,['pending','under_review','quote_draft','quote_sent','quote_sent'],true) ? $status : '', 'search' => $search],
            'counts' => ['pending'=>QuoteRequest::where('status','pending')->count(),'under_review'=>QuoteRequest::where('status','under_review')->count(),'quote_draft'=>QuoteRequest::where('status','quote_draft')->count(),'quote_sent'=>QuoteRequest::where('status','quote_sent')->count(),'total'=>QuoteRequest::count()],
        ]);
    }
    public function show(QuoteRequest $quoteRequest): Response {
        $quoteRequest->load(['user:id,name,email','service:id,name','sourceLanguage:id,name','targetLanguage:id,name','currency:id,code,symbol,name','documents','quotes.currency:id,code,symbol']);
        return Inertia::render('admin/quote-requests/show', ['quoteRequest'=>$quoteRequest]);
    }
    public function markUnderReview(QuoteRequest $quoteRequest): RedirectResponse {
        if ($quoteRequest->status === 'pending') $quoteRequest->update(['status'=>'under_review']);
        return back()->with('success','Request marked as under review.');
    }
}
