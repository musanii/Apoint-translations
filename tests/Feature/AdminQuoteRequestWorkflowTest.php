<?php
use App\Models\Currency;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestDocument;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
function makeAdminWorkflowRequest(User $owner): QuoteRequest {
 $service=Service::create(['name'=>'Translation','slug'=>'workflow-translation','is_active'=>true,'sort_order'=>1]);
 $currency=Currency::create(['code'=>'KES','name'=>'Kenyan Shilling','symbol'=>'KSh','decimal_places'=>2,'is_active'=>true,'is_default'=>true]);
 return QuoteRequest::create(['user_id'=>$owner->id,'service_id'=>$service->id,'currency_id'=>$currency->id,'status'=>'pending','delivery_method'=>'digital','quantity'=>100,'unit'=>'word']);
}
test('admin inbox is protected from customers and guests',function(){
 $customer=User::factory()->create();$admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
 $this->get(route('admin.quote-requests.index'))->assertRedirect(route('login'));
 $this->actingAs($customer)->get(route('admin.quote-requests.index'))->assertForbidden();
 $this->actingAs($admin)->get(route('admin.quote-requests.index'))->assertOk()->assertInertia(fn($p)=>$p->component('admin/quote-requests/index'));
});
test('administrator can mark a request under review',function(){
 $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();$req=makeAdminWorkflowRequest(User::factory()->create());
 $this->actingAs($admin)->post(route('admin.quote-requests.review',$req))->assertRedirect()->assertSessionHas('success');
 expect($req->fresh()->status)->toBe('under_review');
});
test('admin draft quote subtotal and total are calculated server side',function(){
 $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();$req=makeAdminWorkflowRequest(User::factory()->create());
 $this->actingAs($admin)->post(route('admin.quote-requests.quotes.store',$req),['pricing_type'=>'per_word','quantity'=>100,'unit'=>'word','rate'=>2.5,'express_fee'=>10,'certification_fee'=>5])->assertRedirect()->assertSessionHas('success');
 $quote=Quote::sole();
 expect($quote->subtotal)->toBe('250.00')->and($quote->total)->toBe('265.00')->and($quote->status)->toBe(Quote::STATUS_DRAFT)->and($req->fresh()->status)->toBe('quote_draft');
});
test('customer cannot create draft quotes',function(){
 $customer=User::factory()->create();$req=makeAdminWorkflowRequest($customer);
 $this->actingAs($customer)->post(route('admin.quote-requests.quotes.store',$req),['pricing_type'=>'fixed','subtotal'=>500])->assertForbidden();
 $this->assertDatabaseCount('quotes',0);
});
test('admin can download request files but customers cannot',function(){
 Storage::fake('local');$admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();$req=makeAdminWorkflowRequest(User::factory()->create());$path="quote-requests/{$req->id}/file.pdf";Storage::disk('local')->put($path,'file contents');
 $doc=QuoteRequestDocument::create(['quote_request_id'=>$req->id,'original_name'=>'file.pdf','stored_name'=>'file.pdf','disk'=>'local','path'=>$path,'mime_type'=>'application/pdf','extension'=>'pdf','size'=>13]);
 $this->actingAs($admin)->get(route('admin.quote-request-documents.download',$doc))->assertOk()->assertHeader('content-disposition');
 $this->actingAs(User::factory()->create())->get(route('admin.quote-request-documents.download',$doc))->assertForbidden();
});
