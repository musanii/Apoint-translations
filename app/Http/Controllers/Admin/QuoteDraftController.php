<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateQuoteDraftRequest;
use App\Models\QuoteRequest;
use App\Services\QuoteCreationService;
use Illuminate\Http\RedirectResponse;
class QuoteDraftController extends Controller
{
    public function store(CreateQuoteDraftRequest $request, QuoteRequest $quoteRequest, QuoteCreationService $service): RedirectResponse {
        $d=$request->validated(); $fixed=$d['pricing_type']==='fixed';
        $qty=$fixed?null:(float)$d['quantity']; $unit=$fixed?null:$d['unit']; $rate=$fixed?null:(float)$d['rate'];
        $subtotal=$fixed?(float)$d['subtotal']:round($qty*$rate,2);
        $service->create(quoteRequest:$quoteRequest,pricingType:$d['pricing_type'],quantity:$qty,unit:$unit,rate:$rate,subtotal:$subtotal,
            expressFee:(float)($d['express_fee']??0),certificationFee:(float)($d['certification_fee']??0),courierFee:(float)($d['courier_fee']??0),
            customerNotes:$d['customer_notes']??null,adminNotes:$d['admin_notes']??null);
        $quoteRequest->update(['status'=>'quote_draft']);
        return back()->with('success','Quotation draft saved. It has not been sent to the customer.');
    }
}
