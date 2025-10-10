<?php

// app/Http/Controllers/Api/WhatsAppTemplateApiController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsAppTemplate;

class WhatsAppTemplateApiController extends Controller
{
  public function index(Request $req) {
    $cid = $req->user()->company_id;
    return WhatsAppTemplate::where('company_id',$cid)->latest()->paginate(20);
  }

  public function store(Request $req) {
    $cid = $req->user()->company_id;
    $data = $req->validate([
      'name'=>'required|string|max:120',
      'provider_template'=>'required|string|max:160',
      'language'=>'required|string|max:20',
      'category'=>'nullable|string|max:40',
      'header'=>'nullable|string',
      'body'=>'required|string',
      'footer'=>'nullable|string',
      'buttons'=>'nullable|array',
      'status'=>'required|in:active,draft,archived',
    ]);
    $data['company_id'] = $cid;
    $tpl = WhatsAppTemplate::create($data);
    return response()->json(['data'=>$tpl], 201);
  }

  public function show(Request $req, $id) {
    $tpl = WhatsAppTemplate::where('company_id',$req->user()->company_id)->findOrFail($id);
    return ['data'=>$tpl];
  }

  public function update(Request $req, $id) {
    $tpl = WhatsAppTemplate::where('company_id',$req->user()->company_id)->findOrFail($id);
    $data = $req->validate([
      'name'=>'required|string|max:120',
      'provider_template'=>'required|string|max:160',
      'language'=>'required|string|max:20',
      'category'=>'nullable|string|max:40',
      'header'=>'nullable|string',
      'body'=>'required|string',
      'footer'=>'nullable|string',
      'buttons'=>'nullable|array',
      'status'=>'required|in:active,draft,archived',
    ]);
    $tpl->update($data);
    return ['data'=>$tpl];
  }

  public function destroy(Request $req, $id) {
    $tpl = WhatsAppTemplate::where('company_id',$req->user()->company_id)->findOrFail($id);
    $tpl->delete();
    return response()->noContent();
  }

  public function preview(Request $req, $id) {
    $tpl = WhatsAppTemplate::where('company_id',$req->user()->company_id)->findOrFail($id);
    // You can interpolate variables here if you wish
    return [
      'header' => $tpl->header,
      'body'   => $tpl->body,
      'footer' => $tpl->footer,
      'buttons'=> $tpl->buttons,
    ];
  }
}
