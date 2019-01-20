<?php

namespace App\Modules\Invoice\Http\Controllers;

use App\Lib\sortBydate;
use App\Lib\TemplateHeader;
use App\Models\AccountChart\Account;
use App\Models\Branch\Branch;
use App\Models\Inventory\Item;
use App\Models\Inventory\Stock;
use App\Models\Manpower\Manpower_service;
use App\Models\ManualJournal\JournalEntry;
use App\Models\Moneyin\InvoiceEntry;
use App\Models\Recruit\Recruitorder;
use App\Models\Template\HeaderTemplate;
use App\Models\Visa\Ticket\Order\Order;
use App\Models\VisaStamp\VisaStamp;
use App\Modules\Invoice\Http\Response\Payment;
use DateTime;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

use DB;

use App\Models\Moneyin\Invoice;
use App\Models\Contact\Contact;
use App\Models\ManualJournal\Journal;
use App\Models\ManualJournal\JournalEntries;
use App\Models\Moneyin\ExcessPayment;
use App\Models\Moneyin\PaymentReceiveEntryModel;
use App\Models\Moneyin\PaymentReceives;
use App\Models\Moneyin\CreditNotePayment;
use App\Models\Moneyin\CreditNote;
use App\Models\Contact\Agent;
use App\Models\Invoice\InvoiceDueDates;
use App\Models\OrganizationProfile\OrganizationProfile;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use NumberToWords\NumberToWords;
class InvoiceWebController extends Controller
{
    public function index(Request $request)
    {
        $auth_id = Auth::id();
        $sort= new sortBydate();
        $branch_id = session('branch_id');
        $branchs = Branch::orderBy('id','asc')->get();
        $invoices = [];
        $condition = "YEAR(str_to_date(invoice_date,'%d-%m-%Y')) = YEAR(CURDATE()) AND MONTH(str_to_date(invoice_date,'%d-%m-%Y')) = MONTH(CURDATE())";
        if($branch_id==1) {
            if($request->due)
            {
                $invoices = Invoice::where('due_amount','!=',0)->get()->toArray();
            }
            else
            {
                $invoices = Invoice::whereRaw($condition)->get()->toArray();
            }


        }
        else
        {
            $invoices = Invoice::select(DB::raw('invoices.*'))->whereRaw($condition)->join('users','users.id','=','invoices.created_by')->where('users.branch_id',$branch_id)->get()->toArray();
        }

        try{
            $invoices= $sort->get('\App\Models\Moneyin\Invoice','invoice_date','d-m-Y',$invoices);
            return view('invoice::invoice.index',compact('invoices','branchs'));
        }catch (\Exception $exception){
            return view('invoice::invoice.index',compact('invoices','branchs'));
        }

    }

    public function search(Request $request)
    {
        $branchs = Branch::orderBy('id','asc')->get();
        $branch_id =  $request->branch_id;
        if(session('branch_id')==1)
        {
            $branch_id =  $request->branch_id?$request->branch_id:session('branch_id');
        }
        else
        {
            $branch_id = session('branch_id');
        }
        $from_date =  date('Y-m-d',strtotime($request->from_date));
        $to_date =  date('Y-m-d',strtotime($request->to_date));
        $condition = "str_to_date(invoice_date, '%d-%m-%Y') between '$from_date' and '$to_date'";
        $invoices = [];
        if($branch_id==1)
        {
            $invoic = Invoice::whereRaw($condition)->get()->toArray();
        }else{
            $invoic = Invoice::select(DB::raw('invoices.*'))->whereRaw($condition)->join('users','users.id','=','invoices.created_by')->where('branch_id',$branch_id)->get()->toArray();

        }
        $sort= new sortBydate();
        try{
           $invoices= $sort->get('\App\Models\Moneyin\Invoice','invoice_date','d-m-Y',$invoic);
           return view('invoice::invoice.index',compact('invoices','branchs','branch_id','from_date','to_date'));
       }catch (\Exception $exception){

           return view('invoice::invoice.index',compact('invoices','branchs','branch_id','from_date','to_date'));
       }

   }

   public function create()
   {

       // $customers = Contact::whereIn('contact_category_id', [1,2])->get();
    $customers = Contact::all();
    $agents = $customers;
    $account = Account::whereIn("account_type_id",[4,5])->get(['id','account_name']);

    $invoices = Invoice::count();

    if($invoices>0)
    {
        $invoice = Invoice::orderBy('created_at', 'desc')->first();
        $invoice_number = $invoice['invoice_number'];
        $invoice_number = $invoice_number + 1;
    }
    else
    {
        $invoice_number = 1;
    }
    $invoice_number = str_pad($invoice_number, 6, '0', STR_PAD_LEFT);
    return view('invoice::invoice.create', compact('customers','invoice_number', 'agents','status','account'));
}

public function ajaxcheck(Request $request){

    $data=$request->all();
    $helper = new \App\Lib\Helpers;
    $status1 = $helper->checkItemQuantity($data);

    if($status1){
      return 1;
  }else{
    return 0;
}
}

public function ajaxShowItem(Request $request){

    $item=Item::where('id',$request->id)
    ->first();
    $stock = ($item->total_purchases - $item->total_sales);

    return response($stock) ;

}

public function ajaxCreateStock(Request $request){

    $item=Item::where('id',$request->id)
    ->first();

    $st =($request->quantity)-($item->total_purchases - $item->total_sales);

    $stock=new Stock();
    $stock->total=$st;
    $stock->date=date('d-m-Y',strtotime(\Carbon\Carbon::now()));
    $stock->item_category_id=$item->item_category_id;
    $stock->item_id=$item->id;
    $stock->branch_id=$item->branch_id;
    $stock->created_by=$item->created_by;
    $stock->updated_by=$item->updated_by;
    $stock->save();

    if ($stock){

        $total=$item->total_purchases+$st;
        $item=Item::find($request->id);
        $item->total_purchases=$total;
        $item->save();
        return response($item) ;

    }


}

public function ajaxInvoicecheck(Request $request){

    $data=$request->all();

    $helper = new \App\Lib\Helpers;

    $status1 = $helper->checkItemQuantity($data);

    if ($status1) {
        return response(['status'=>(string)$status1]) ;

    }else{
        return response(['status'=>(string)$status1]) ;
    }
}

public function ajaxEditcheck(Request $request){

    $data=$request->all();

    $helper = new \App\Lib\Helpers;

    $status1 = $helper->checkItemQuantity($data);
    if($status1){
        return 1;
    }else{
        return 0;
    }
}

public function store(Request $request)
{

    $validatiolist = [
        'customer_id' => 'required',
        'invoice_date' => 'required',
        'due_date' => 'required',
        'item_id.*' => 'required',
        'quantity.*' => 'required',
        'rate.*' => 'required',
        'tax_id.*' => 'required',
        'amount.*' => 'required',
        'account_id' => 'required',
    ];
    if($request->check_payment)
    {
        $validatiolist["payment_account"] = "required";
        $validatiolist["payment_amount"] = "required||numeric";
    }
    $payment =  new Payment();



    $this->validate($request, $validatiolist);

    try {
        DB::beginTransaction();
        $data = $request->all();
        if ($data['commission_type'] == 1) {
            $agent_commission_amount = ($data['agentcommissionAmount'] / 100) * ($data['total_amount']-$data["shipping_charge"]-$data["tax_total"]);
        } else {
            $agent_commission_amount = $data['agentcommissionAmount'];
        }
        $user_id = Auth::user()->id;
        $helper = new \App\Lib\Helpers;
        $check_Item_Quantity = $helper->checkItemQuantity($data);
        if (!$check_Item_Quantity)
        {

          throw new \Exception("Quantity is not available for some item. Please add the invoice again!!!");
      }

      $invoices = Invoice::count();

      if($invoices>0)
      {
        $invoice = Invoice::orderBy('created_at', 'desc')->first();
        $invoice_number = $invoice['invoice_number'];
        $invoice_number = $invoice_number + 1;
    }
    else
    {
        $invoice_number = 1;
    }
    $invoice_number = str_pad($invoice_number, 6, '0', STR_PAD_LEFT);

    $invoice = new Invoice;
    $invoice->invoice_number        = $invoice_number;
    $invoice->invoice_date          = date("Y-m-d", strtotime($data['invoice_date']));
    $invoice->payment_date          = date("Y-m-d", strtotime($data['due_date']));
    $invoice->reference             = $data['reference'];
    $invoice->customer_note         = $data['customer_note'];
    $invoice->personal_note         = $data['personal_note'];
    $invoice->tax_total             = $data['tax_total'];
    $invoice->shipping_charge       = $data['shipping_charge'];
    $invoice->adjustment            = $data['adjustment'];
    $invoice->total_amount          = $data['total_amount'];
    $invoice->due_amount            = $data['total_amount'];
    if($request->save)
    {
        $invoice->save                  = 1;
    }
    $invoice->customer_id           = $data['customer_id'];
    $invoice->created_by            = $user_id;
    $invoice->updated_by            = $user_id;
    if ($request->hasFile('file'))
    {
        $file = $request->file('file');
        $file_name = $file->getClientOriginalName();
        $without_extention = substr($file_name, 0, strrpos($file_name, "."));
        $file_extention = $file->getClientOriginalExtension();
        $num = rand(1, 500);
        $new_file_name = "invoice-".$invoice_number. '.' . $file_extention;
        $success = $file->move('uploads/invoice', $new_file_name);

        if ($success)
        {
            $invoice->file_url = 'uploads/invoice/' . $new_file_name;
            $invoice->file_name = $new_file_name;
        }
    }

    $invoice->invoice_number = $invoice_number;
    $invoice->invoice_date = $data['invoice_date'];
    $invoice->payment_date = $data['due_date'];
    $invoice->customer_note = $data['customer_note'];
    $invoice->personal_note = $data['personal_note'];
    $invoice->tax_total = $data['tax_total'];
    $invoice->shipping_charge = $data['shipping_charge'];
    $invoice->adjustment = $data['adjustment'];
    $invoice->total_amount = $data['total_amount'];
    $invoice->due_amount = $data['total_amount'];
    $invoice->customer_id = $data['customer_id'];
    $invoice->created_by = $user_id;
    $invoice->updated_by = $user_id;
    if ($data['commission_type'] && $data['agent_id'] && $data['agentcommissionAmount'])
    {
        $invoice->agents_id = $data['agent_id'];
        $invoice->agentcommissionAmount = $data['agentcommissionAmount'];
        $invoice->commission_type = $data['commission_type'];
    }
    if ($invoice->save())
    {
                   // $invoice = Invoice::orderBy('created_at', 'desc')->first();
        $invoice_id = $invoice['id'];
        $i = 0;
        foreach ($data['item_id'] as $account)
        {
            if (!$data['discount'][$i]) {

                $invoice_entry[] = [
                    'quantity' => $data['quantity'][$i],
                    'rate' => $data['rate'][$i],
                    'description' => $data['description'][$i],
                    'amount' => $data['amount'][$i],
                    'discount' => 0,
                    'discount_type' => 0,
                    'item_id' => $data['item_id'][$i],
                    'invoice_id' => $invoice_id,
                    'tax_id' => $data['tax_id'][$i],
                    'account_id' => $data['account_id'][$i],
                    'created_by' => $user_id,
                    'updated_by' => $user_id,
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString(),
                ];
            } else {


                $invoice_entry[] = [
                    'quantity' => $data['quantity'][$i],
                    'rate' => $data['rate'][$i],
                    'description' => $data['description'][$i],
                    'amount' => $data['amount'][$i],
                    'discount' => $data['discount'][$i],
                    'discount_type' => $data['discount_type'][$i],
                    'item_id' => $data['item_id'][$i],
                    'invoice_id' => $invoice_id,
                    'tax_id' => $data['tax_id'][$i],
                    'account_id' => $data['account_id'][$i],
                    'created_by' => $user_id,
                    'updated_by' => $user_id,
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString(),
                ];
            }

            if ($data['discount'][$i]==1)
            {
                $data['discount'][$i]=($data['discount'][$i]*$data['quantity'][$i]*100)/$data['rate'][$i];
            }
            $i++;
        }
        if(DB::table('invoice_entries')->insert($invoice_entry))
            {
                if($request->submit)
                {
                    $status = $this->insertManualJournalEntries($data, $agent_commission_amount);
                    $status2 = $helper->updateItemAfterCreatingInvoice($data);
                            //payment
                    if($request->check_payment) {
                        $payment_receive= $payment->makePaymentReceive($request,$invoice_id);   
                        $invoice->payment_recieve_id = $payment_receive['id'];
                        $invoice->due_amount = $invoice->due_amount-$request->payment_amount;
                        $invoice->save();
                    }
                    if(!$status || !$status2)
                    {
                       throw new \Exception("invoice fail");
                   }
               }
           }
       }
       DB::commit();
       return redirect()
       ->route('invoice')
       ->with('alert.status', 'success')
       ->with('alert.message', 'Invoice added successfully!');
   }
   catch(\Exception $e)
   {
    DB::rollback();
    $mesg = $e->getMessage();
    return redirect()
    ->route('invoice')
    ->with('alert.status', 'delete')
    ->with('alert.message', " $mesg");
}
}

public function show($id)
{
    $invoice_due_dates = InvoiceDueDates::where('invoice_id', '=', $id)->get();
    $invoices = [];
    try{
        $invoice = Invoice::find($id);
        if(!$invoice)
        {
           return back()->with('alert.status', 'warning')
           ->with('alert.message', 'Invoice not found!');
        }

        $payment_receive_entries = PaymentReceiveEntryModel::where('invoice_id', $id)->get();
        $credit_receive_entries = CreditNotePayment::where('invoice_id', $id)->get();
        $excess_receive_entries = ExcessPayment::where('invoice_id', $id)->get();
        $invoices = Invoice::orderBy('invoice_date','desc')->take(10)->get()->toArray();
        $sort= new sortBydate();
        $invoices= $sort->get('\App\Models\Moneyin\Invoice','invoice_date','d-m-Y',$invoices);

        $invoice_entries = InvoiceEntry::where('invoice_id', $id)->get();
        $invoice_discount_count = InvoiceEntry::where([['invoice_id' ,'=', $id],['discount','!=',0]])
        ->count();

       $sub_total = 0;
       $OrganizationProfile = OrganizationProfile::find(1);
       foreach ($invoice_entries as $invoice_entry)
       {
        $sub_total = $sub_total + $invoice_entry->amount;

    }
    return view('invoice::invoice.show', compact('invoice', 'invoice_entries', 'sub_total','invoices','payment_receive_entries','credit_receive_entries','excess_receive_entries','OrganizationProfile','invoice_discount_count', 'invoice_due_dates'));
    }catch (\Exception $exception){
        return back()->with('alert.status', 'delete')
        ->with('alert.message', 'Invoice not found!');
    }

}
public function showupload(Request $request,$id=null){
    $invoice = Invoice::find($id);
    $validator = Validator::make($request->all(), [
        'file1' => 'required|size:10240',

    ]);


    if($validator->fails()){
        return response("file size not allowed");
    }
    if($request->hasFile('file1')) {
        $file = $request->file('file1');

        if ($invoice->file_url)
        {
            $delete_path = public_path($invoice->file_url);
            if(file_exists($delete_path)){
                $delete = unlink($delete_path);
            }

        }

        $file_name = $file->getClientOriginalName();
        $without_extention = substr($file_name, 0, strrpos($file_name, "."));
        $file_extention = $file->getClientOriginalExtension();
        $num = rand(1, 500);
        $new_file_name = "invoice-".$invoice->invoice_number.'.'.$file_extention;

        $success = $file->move('uploads/invoice', $new_file_name);

        if ($success) {
            $invoice->file_url = 'uploads/invoice/' . $new_file_name;
            $invoice->file_name = $new_file_name;

            $invoice->save();
            return response("success");
        }else{
            return response("success");
        }
    }else{
        return response("file not found");
    }

}

public function edit(Request $request,$id)
{
    $account = Account::whereIn("account_type_id",[4,5])->get(['id','account_name']);
        //$customers = Contact::whereIn('contact_category_id', [1,2])->get();
    $customers = Contact::all();
    $agents = $customers;
    $invoice = Invoice::find($id);

    return view('invoice::invoice.edit', compact('account','customers', 'invoice', 'agents'));
}

public function update(Request $request, $id)
{
       // return $request->all();
    $this->validate($request, [
        'customer_id'    => 'required',
        'invoice_number' => 'required',
        'invoice_date'   => 'required',
        'due_date'       => 'required',
        'item_id.*'      => 'required',
        'quantity.*'     => 'required',
        'rate.*'         => 'required',
        'tax_id.*'       => 'required',
        'amount.*'       => 'required',
    ]);

    if ($request->submit != 'save'){

        try {
            $data           = $request->all();
            $invoice        = Invoice::find($id);

            $total_received_payment     = $invoice->total_amount - $invoice->due_amount;

            if($data['total_amount'] >= $total_received_payment){

                $invoice->due_amount    = $data['total_amount'] - $total_received_payment;

            }else{

                return redirect()
                    ->route('invoice_edit', ['id' => $id])
                    ->with('alert.status', 'danger')
                    ->with('alert.message', 'Sorry! Invoice Total Amount cannot be smaller than Total Received Payment.'); 
            }

            if ($data['commission_type'] == 1) {
                $agent_commission_amount = ($data['agentcommissionAmount'] / 100) * ($data['total_amount']-$data["shipping_charge"]-$data["tax_total"]);
            } else {
                $agent_commission_amount = $data['agentcommissionAmount'];
            }

            $user_id = Auth::user()->id;

            $helper = new \App\Lib\Helpers;
            $helper->updateItemAfterUpdatingInvoice($data);

                //Update

            $created_by = $invoice->created_by;
            $created_at = $invoice->created_at->toDateTimeString();
            $updated_at = \Carbon\Carbon::now()->toDateTimeString();

            if ($request->hasFile('file')) {
                $file = $request->file('file');

                if ($invoice->file_url) {
                    $delete_path = public_path($invoice->file_url);
                    $delete = unlink($delete_path);
                }

                $file_name = $file->getClientOriginalName();
                $without_extention = substr($file_name, 0, strrpos($file_name, "."));
                $file_extention = $file->getClientOriginalExtension();
                $num = rand(1, 500);
                $new_file_name = $data['invoice_number']. '.' . $file_extention;

                $success = $file->move('uploads/invoice', $new_file_name);

                if ($success) {
                    $invoice->file_url = 'uploads/invoice/' . $new_file_name;
                    $invoice->file_name = $new_file_name;
                }
            }

            $invoice->invoice_number = $data['invoice_number'];
            $invoice->invoice_date = date("d-m-Y", strtotime($data['invoice_date']));
            $invoice->payment_date = date("d-m-Y", strtotime($data['due_date']));
            $invoice->reference = $data['reference'];
            $invoice->customer_note = $data['customer_note'];
            $invoice->personal_note = $data['personal_note'];
            $invoice->tax_total = $data['tax_total'];
            $invoice->shipping_charge = $data['shipping_charge'];
            $invoice->adjustment = $data['adjustment'];
            $invoice->total_amount = $data['total_amount'];
            $invoice->due_amount = $data['total_amount'] - $total_received_payment;
            $invoice->save = null;
            $invoice->customer_id = $data['customer_id'];
            $invoice->created_by = $created_by;
            $invoice->updated_by = $user_id;
            $invoice->created_at = $created_at;
            $invoice->updated_at = $updated_at;

//                if ($data['commission_type'] && $data['agent_id'] && $data['agentcommissionAmount']) {
            if ($data['commission_type']  && $data['agentcommissionAmount']) {
                    //$invoice->agents_id = $data['agent_id'];
                $invoice->agentcommissionAmount = $data['agentcommissionAmount'];
                $invoice->commission_type = $data['commission_type'];
            }


            $invoice_entry_update = [];
            if ($invoice->update()){
                $invoice_entry = Invoice::find($id)->invoiceEntries();

                if ($invoice_entry->delete()) {

                }
                $i = 0;
                foreach ($data['item_id'] as $account) {

                    if (!$data['discount'][$i]) {
                        $invoice_entry_update[] = [
                            'quantity' => $data['quantity'][$i],
                            'rate' => $data['rate'][$i],
                            'description' => $data['description'][$i],
                            'amount' => $data['amount'][$i],
                            'discount' => 0,
                            'discount_type' => 0,
                            'item_id' => $data['item_id'][$i],
                            'invoice_id' => $id,
                            'tax_id' => $data['tax_id'][$i],
                            'account_id' => $data['account_id'][$i],
                            'created_by' => $created_by,
                            'updated_by' => $user_id,
                            'created_at' => $created_at,
                            'updated_at' => $updated_at,
                        ];
                    } else {
                        $invoice_entry_update[] = [
                            'quantity' => $data['quantity'][$i],
                            'rate' => $data['rate'][$i],
                            'description' => $data['description'][$i],
                            'amount' => $data['amount'][$i],
                            'discount' => $data['discount'][$i],
                            'discount_type' => $data['discount_type'][$i],
                            'item_id' => $data['item_id'][$i],
                            'invoice_id' => $id,
                            'tax_id' => $data['tax_id'][$i],
                            'account_id' => $data['account_id'][$i],
                            'created_by' => $created_by,
                            'updated_by' => $user_id,
                            'created_at' => $created_at,
                            'updated_at' => $updated_at,
                        ];
                    }
                    if ($data['discount_type'][$i]==1) {
                        $data['discount'][$i]=$data['discount'][$i];
                    }else{
                        $data['discount'][$i]=$data['discount'][$i];
                    }
                    $i++;
                }

                if (DB::table('invoice_entries')->insert($invoice_entry_update)){
                    $this->updateManualJournalEntries($data, $id, $agent_commission_amount);
                    return redirect()
                    ->route('invoice')
                    ->with('alert.status', 'success')
                    ->with('alert.message', 'Invoice updated successfully!');
                }
            }
            return redirect()
            ->route('invoice_edit', ['id' => $id])
            ->with('alert.status', 'danger')
            ->with('alert.message', 'Something went to wrong! please check your input field!!!');
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }else{
        try {
            $data = $request->all();
                //return $data;

            if ($data['commission_type'] == 1) {
                $agent_commission_amount = ($data['agentcommissionAmount'] / 100) * $data['total_amount'];
            } else {
                $agent_commission_amount = $data['agentcommissionAmount'];
            }


            $user_id = Auth::user()->id;

            $helper = new \App\Lib\Helpers;
            //    $helper->updateItemAfterUpdatingInvoice($data);

            $invoice = Invoice::find($id);

            $created_by = $invoice->created_by;
            $created_at = $invoice->created_at->toDateTimeString();
            $updated_at = \Carbon\Carbon::now()->toDateTimeString();

            if($request->hasFile('file')) {
                $file = $request->file('file');

                if ($invoice->file_url) {
                    $delete_path = public_path($invoice->file_url);
                    $delete = unlink($delete_path);
                }

                $file_name = $file->getClientOriginalName();
                $without_extention = substr($file_name, 0, strrpos($file_name, "."));
                $file_extention = $file->getClientOriginalExtension();
                $num = rand(1, 500);
                $new_file_name = $data['invoice_number'] . '.' . $file_extention;

                $success = $file->move('uploads/invoice', $new_file_name);

                if ($success) {
                    $invoice->file_url = 'uploads/invoice/' . $new_file_name;
                    $invoice->file_name = $new_file_name;
                }
            }

            $invoice->invoice_number = $data['invoice_number'];
            $invoice->invoice_date = date("d-m-Y", strtotime($data['invoice_date']));
            $invoice->payment_date = date("d0", strtotime($data['due_date']));
            $invoice->reference = $data['reference'];
            $invoice->customer_note = $data['customer_note'];
            $invoice->personal_note = $data['personal_note'];
            $invoice->tax_total = $data['tax_total'];
            $invoice->shipping_charge = $data['shipping_charge'];
            $invoice->adjustment = $data['adjustment'];
            $invoice->total_amount = $data['total_amount'];
            $invoice->due_amount = $data['total_amount'];
            $invoice->save = 1;
            $invoice->customer_id = $data['customer_id'];
            $invoice->created_by = $created_by;
            $invoice->updated_by = $user_id;
            $invoice->created_at = $created_at;
            $invoice->updated_at = $updated_at;

            if ($data['commission_type'] && $data['agent_id'] && $data['agentcommissionAmount']) {
                $invoice->agents_id = $data['agent_id'];
                $invoice->agentcommissionAmount = $data['agentcommissionAmount'];
                $invoice->commission_type = $data['commission_type'];
            }


            $invoice_entry_update = [];
            if ($invoice->update()){

                    //Update Time 

                $created = Invoice::find($id);
                $created_by = $created->created_by;
                $created_at = $created->created_at->toDateTimeString();
                $updated_at = \Carbon\Carbon::now()->toDateTimeString();

                $invoice_entry = Invoice::find($id)->invoiceEntries();

                if ($invoice_entry->delete()){

                }
                $i = 0;
                foreach ($data['item_id'] as $account) {

                    if (!$data['discount'][$i]) {
                        $invoice_entry_update[] = [
                            'quantity' => $data['quantity'][$i],
                            'rate' => $data['rate'][$i],
                            'description' => $data['description'][$i],
                            'amount' => $data['amount'][$i],
                            'discount' => 0,
                            'discount_type' => 0,
                            'item_id' => $data['item_id'][$i],
                            'invoice_id' => $id,
                            'tax_id' => $data['tax_id'][$i],
                            'account_id' => $data['account_id'][$i],
                            'created_by' => $created_by,
                            'updated_by' => $user_id,
                            'created_at' => $created_at,
                            'updated_at' => $updated_at,
                        ];
                    } else {
                        $invoice_entry_update[] = [
                            'quantity' => $data['quantity'][$i],
                            'rate' => $data['rate'][$i],
                            'description' => $data['description'][$i],
                            'amount' => $data['amount'][$i],
                            'discount' => $data['discount'][$i],
                            'discount_type' => $data['discount_type'][$i],
                            'item_id' => $data['item_id'][$i],
                            'invoice_id' => $id,
                            'tax_id' => $data['tax_id'][$i],
                            'account_id' => $data['account_id'][$i],
                            'created_by' => $created_by,
                            'updated_by' => $user_id,
                            'created_at' => $created_at,
                            'updated_at' => $updated_at,
                        ];
                    }

                    $i++;
                }

                if (DB::table('invoice_entries')->insert($invoice_entry_update)) {
                       // $this->updateManualJournalEntries($data, $id, $agent_commission_amount);
                    return redirect()
                    ->route('invoice')
                    ->with('alert.status', 'success')
                    ->with('alert.message', 'Invoice updated successfully!');
                }
            }
            return redirect()
            ->route('invoice', ['id' => $id])
            ->with('alert.status', 'success')
            ->with('alert.message', 'Invoice updated successfully!');
        } catch (Exception $e) {
            return $e->getMessage();
        }

    }
}

public function adjustmentUpdate(Request $request, $id){

    return redirect()
            ->route('invoice')
            ->with('alert.status', 'danger')
            ->with('alert.message', 'Sorry, This Action is not active now! Please Contact with Administration.');

    // try
    // {
    //     $adjustment_data = $request->all();
    //     $user_id =  Auth::id();
    //     $invoice = Invoice::find($id);

    //     $vat_adjustment_old_data = $invoice['vat_adjustment'];
    //     $tax_adjustment_old_data = $invoice['tax_adjustment'];
    //     $other_adjustment_old_data = $invoice['others_adjustment'];

    //     $vat_adjustment_data = !empty($adjustment_data['vat_adjustment']) ? floatval($adjustment_data['vat_adjustment']) : floatval(0);
    //     $tax_adjustment_data = !empty($adjustment_data['tax_adjustment']) ? floatval($adjustment_data['tax_adjustment']) : floatval(0);
    //     $other_adjustment_data = !empty($adjustment_data['others_adjustment']) ? floatval($adjustment_data['others_adjustment']) : floatval(0);


    //     if((!empty($adjustment_data['vat_adjustment']) || !empty($adjustment_data['tax_adjustment'])))
    //     {
    //         if((floatval($adjustment_data['vat_adjustment']) + floatval($adjustment_data['tax_adjustment']))>0)
    //         {
    //             $oldjournal= JournalEntry::where("invoice_id", $invoice)->where("jurnal_type","invoice")->where("account_name_id",9)->where("debit_credit",1)->latest()->first();

    //             if($oldjournal)
    //             {
    //                 $oldjournal->amount = $vat_adjustment_data + $tax_adjustment_data;
    //                 $oldjournal->updated_by = $user_id;
    //                 $oldjournal->save();
    //             }
    //             else
    //             {
    //                 $journal_entry = new JournalEntry;
    //                 $journal_entry->debit_credit = 1;
    //                 $journal_entry->amount  = $vat_adjustment_data + $tax_adjustment_data;
    //                 $journal_entry->account_name_id  = 9;
    //                 $journal_entry->jurnal_type  = "invoice";
    //                 $journal_entry->invoice_id  = $invoice['id'];

    //                 $journal_entry->created_by = $user_id;
    //                 $journal_entry->updated_by = $user_id;
    //                 $journal_entry->assign_date = date("Y-m-d", strtotime($invoice['invoice_date']));
    //                 $journal_entry->contact_id  = $invoice['customer_id'];
    //                 $journal_entry->save();
    //             }

    //         }

    //     }

    //     if(!empty($adjustment_data['others_adjustment']))
    //     {
    //         if($adjustment_data['others_adjustment']>0)
    //         {
    //             $oldjournal= JournalEntry::where("invoice_id", $invoice)->where("jurnal_type","invoice")->where("account_name_id",18)->where("debit_credit",1)->latest()->first();

    //             if($oldjournal)
    //             {
    //                 $oldjournal->amount = $other_adjustment_data;
    //                 $oldjournal->updated_by = $user_id;
    //                 $oldjournal->save();
    //             }else{
    //                 $journal_entry = new JournalEntry;
    //                 $journal_entry->debit_credit = 1;
    //                 $journal_entry->amount  = $other_adjustment_data;
    //                 $journal_entry->account_name_id  = 18;
    //                 $journal_entry->jurnal_type  = "invoice";
    //                 $journal_entry->invoice_id  = $invoice['id'];

    //                 $journal_entry->created_by = $user_id;
    //                 $journal_entry->updated_by = $user_id;
    //                 $journal_entry->assign_date = date("Y-m-d", strtotime($invoice['invoice_date']));
    //                 $journal_entry->contact_id  = $invoice['customer_id'];
    //                 $journal_entry->save();
    //             }

    //         }

    //     }

    //     $oldjournal= JournalEntry::where("invoice_id", $invoice)->where("jurnal_type","invoice")->where("account_name_id",5)->where("debit_credit",0)->latest()->first();

    //     if($oldjournal){
    //         $oldjournal->amount = $other_adjustment_data + $vat_adjustment_data + $tax_adjustment_data;
    //         $oldjournal->updated_by = $user_id;
    //         $oldjournal->save();
    //     }else{
    //         $journal_entry = new JournalEntry;
    //         $journal_entry->debit_credit = 0;
    //         $journal_entry->amount  = $other_adjustment_data + $vat_adjustment_data + $tax_adjustment_data;
    //         $journal_entry->account_name_id  = 5;
    //         $journal_entry->jurnal_type  = "invoice";
    //         $journal_entry->invoice_id  = $invoice['id'];

    //         $journal_entry->created_by = $user_id;
    //         $journal_entry->updated_by = $user_id;
    //         $journal_entry->assign_date = date("Y-m-d", strtotime($invoice['invoice_date']));
    //         $journal_entry->contact_id  = $invoice['customer_id'];
    //         $journal_entry->save();
    //     }


    //     $invoice->due_amount = $invoice['due_amount'] + $vat_adjustment_old_data + $tax_adjustment_old_data + $other_adjustment_old_data
    //     - $vat_adjustment_data - $tax_adjustment_data - $other_adjustment_data;
    //     $invoice->vat_adjustment = $vat_adjustment_data;
    //     $invoice->tax_adjustment = $tax_adjustment_data;
    //     $invoice->others_adjustment = $other_adjustment_data;

    //     $invoice->update();

    //     return redirect()
    //         ->route('invoice')
    //         ->with('alert.status', 'success')
    //         ->with('alert.message', 'Adjustment Updated Successfully!');

    // }
    // catch (Exception $e)
    // {
    //     return redirect()
    //         ->route('invoice')
    //         ->with('alert.status', 'danger')
    //         ->with('alert.message', 'Sorry, something went wrong! Please try again.');
    // }
}

public function destroy($id)
{
    $helper = new \App\Lib\Helpers;

        //check payment receive is used in this invoice or not
    if($helper->isPaymentReceiveInThisInvoice($id))
    {

        return redirect()
        ->route('invoice')
        ->with('alert.status', 'danger')
        ->with('alert.message', 'Payment receive used in this invoice. First You have to delete payment receive from this invoice.');
    }

        //check credit note is used in this invoice or not
    if($helper->isCreditNoteInThisInvoice($id))
    {
        return redirect()
        ->route('invoice')
        ->with('alert.status', 'danger')
        ->with('alert.message', 'Credit note used in this invoice. First You have to delete credit note from this invoice.');
    }


    $payment_amount = DB::table('payment_receives_entries')
    ->where('invoice_id', $id)
    ->groupBy('payment_receives_id')
    ->selectRaw('sum(amount) as amount, payment_receives_id')
    ->get();

    foreach ($payment_amount as $value)
    {
        $helper->paymentReceiveBackAfterDeleteInvoice($value->payment_receives_id, $value->amount);
    }


    $credit_note = DB::table('credit_note_payments')
    ->where('invoice_id', $id)
    ->groupBy('credit_note_id')
    ->selectRaw('sum(amount) as amount, credit_note_id')
    ->get();

    foreach ($credit_note as $value)
    {
        $helper->creditNoteBackAfterDeleteInvoice($value->credit_note_id, $value->amount);
    }


    $items = InvoiceEntry::where('invoice_id', $id)->get();
    foreach ($items as $item)
    {
        $helper->itemBackAfterDeleteInvoice($item->item_id, $item->quantity);
    }

    $invoice = Invoice::find($id);

    if($invoice)
    {
        if(Order::where('invoice_id',$invoice->id)->first()) {
            $order=Order::where('invoice_id',$invoice->id)->first();
            $order->invoice_id=null;
            $order->save();
        }

        if(Recruitorder::where('invoice_id',$invoice->id)->first()) {
            $recruit=Recruitorder::where('invoice_id',$invoice->id)->first();
            $recruit->invoice_id=null;
            $recruit->save();
        }

        if($invoice->delete())
        {
            $manpower_service=Manpower_service::where('invoice_id',$id)->first();
            if (!empty($manpower_service)){
                $manpower_service->invoice_id=null;
                $manpower_service->save();

            }

            $ticket=Order::where('invoice_id',$id)->first();
            if (!empty($ticket)){
                $ticket->invoice_id=null;
                $ticket->save();
            }

            $recruit=Recruitorder::where('invoice_id',$id)->first();
            if (!empty($recruit)){
                $recruit->invoice_id=null;
                $recruit->save();
            }


            if ($invoice->file_url)
            {
                $delete_path = public_path($invoice->file_url);
                if(file_exists($delete_path)){
                    $delete = unlink($delete_path);
                }

            }
        }

        return redirect()
        ->route('invoice')
        ->with('alert.status', 'danger')
        ->with('alert.message', 'Invoice deleted successfully!!!');
    }



}


public function insertManualJournalEntries($data, $agent_commission_amount)
{
    $user_id = Auth::user()->id;

    $i = 0;
    $discount = 0;
    $account_array = array_fill(1, 100, 0);
    foreach ($data['item_id'] as $account)
    {
        if($data['discount'][$i] == "")
        {

        }
        else
        {
            $amount = $data['quantity'][$i]*$data['rate'][$i];

            if($data['discount_type'][$i] == 1){
                $discount = $discount+$data['discount'][$i];
            }else{
                $discount = $discount + ($data['discount'][$i]*$amount)/100;
            }
                //$discount1 = ($data['discount'][$i]*$amount)/100;
        }

        $account_array[$data['account_id'][$i]] =  $account_array[$data['account_id'][$i]] + ($data['quantity'][$i]*$data['rate'][$i]);

        $i++;
    }

        //return $account_array;

    $invoice = Invoice::orderBy('created_at', 'desc')->first();
    $invoice_id = $invoice['id'];


        //for agent commission manual journal entry
        //if($data['commission_type'] && $data['agent_id'] && $data['agentcommissionAmount'])
    if($data['commission_type'] && $data['agentcommissionAmount'])
    {
        $journal_entry = new JournalEntry;
        $journal_entry->note            = $data['customer_note'];
        $journal_entry->debit_credit    = 1;
        if($data['commission_type'] == 1)
        {
            $journal_entry->amount = $agent_commission_amount;
        }
        else
        {
            $journal_entry->amount = $data['agentcommissionAmount'];
        }

        $journal_entry->account_name_id = 30;
        $journal_entry->jurnal_type     = "sales_commission";
        $journal_entry->invoice_id      = $invoice_id;
        $journal_entry->contact_id      = $data['customer_id'];
        $journal_entry->agent_id        = isset($data['agent_id'])?$data['agent_id']:null;
        $journal_entry->created_by      = $user_id;
        $journal_entry->updated_by      = $user_id;
        $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));
        $journal_entry->save();

        $journal_entry = new JournalEntry;
        $journal_entry->note            = $data['customer_note'];
        $journal_entry->debit_credit    = 0;
        if($data['commission_type'] == 1)
        {
            $journal_entry->amount = $agent_commission_amount;
        }
        else
        {
            $journal_entry->amount = $data['agentcommissionAmount'];
        }
        $journal_entry->account_name_id = 11;
        $journal_entry->jurnal_type     = "sales_commission";
        $journal_entry->invoice_id      = $invoice_id;
        $journal_entry->contact_id      = $data['customer_id'];
        $journal_entry->agent_id        = isset($data['agent_id'])?$data['agent_id']:null;
        $journal_entry->created_by      = $user_id;
        $journal_entry->updated_by      = $user_id;
        $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));
        $journal_entry->save();
    }

        //insert total amount as debit
    $journal_entry = new JournalEntry;
    $journal_entry->note            = $data['customer_note'];
    $journal_entry->debit_credit    = 1;
    $journal_entry->amount          = $data['total_amount'];
    $journal_entry->account_name_id = 5;
    $journal_entry->jurnal_type     = "invoice";
    $journal_entry->invoice_id      = $invoice_id;
    $journal_entry->contact_id      = $data['customer_id'];
    $journal_entry->created_by      = $user_id;
    $journal_entry->updated_by      = $user_id;
    $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

    if($journal_entry->save())
    {

    }
    else
    {
            //delete all journal entry for this invoice...
        $invoice = Invoice::find($invoice_id);
        $invoice->delete();
        return false;
    }

        //insert discount as credit
    if($discount>0)
    {
        $journal_entry = new JournalEntry;
        $journal_entry->note            = $data['customer_note'];
        $journal_entry->debit_credit    = 1;
        $journal_entry->amount          = $discount;
        $journal_entry->account_name_id = 21;
        $journal_entry->jurnal_type     = "invoice";
        $journal_entry->invoice_id      = $invoice_id;
        $journal_entry->contact_id      = $data['customer_id'];
        $journal_entry->created_by      = $user_id;
        $journal_entry->updated_by      = $user_id;
        $journal_entry->assign_date     = date('Y-m-d',strtotime($data['invoice_date']));

        if($journal_entry->save())
        {

        }
        else
        {
                //delete all journal entry for this invoice...
            $invoice = Invoice::find($invoice_id);
            $invoice->delete();
            return false;
        }
    }


        //insert tax total as credit
    if($data['tax_total']>0)
    {
        $journal_entry = new JournalEntry;
        $journal_entry->note            = $data['customer_note'];
        $journal_entry->debit_credit    = 0;
        $journal_entry->amount          = $data['tax_total'];
        $journal_entry->account_name_id = 9;
        $journal_entry->jurnal_type     = "invoice";
        $journal_entry->invoice_id      = $invoice_id;
        $journal_entry->contact_id      = $data['customer_id'];
        $journal_entry->created_by      = $user_id;
        $journal_entry->updated_by      = $user_id;
        $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

        if($journal_entry->save())
        {

        }
        else
        {
                //delete all journal entry for this invoice...
            $invoice = Invoice::find($invoice_id);
            $invoice->delete();
            return false;
        }
    }

        //insert shipping charge as credit
    if($data['shipping_charge']>0)
    {
        $journal_entry = new JournalEntry;
        $journal_entry->note            = $data['customer_note'];
        $journal_entry->debit_credit    = 0;
        $journal_entry->amount          = $data['shipping_charge'];
        $journal_entry->account_name_id = 20;
        $journal_entry->jurnal_type     = "invoice";
        $journal_entry->invoice_id      = $invoice_id;
        $journal_entry->contact_id      = $data['customer_id'];
        $journal_entry->created_by      = $user_id;
        $journal_entry->updated_by      = $user_id;
        $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

        if($journal_entry->save())
        {

        }
        else
        {
                //delete all journal entry for this invoice...
            $invoice = Invoice::find($invoice_id);
            $invoice->delete();
            return false;
        }
    }


        //insert adjustment as credit
    if($data['adjustment'] != 0)
    {
        $journal_entry = new JournalEntry;
        $journal_entry->note                = $data['customer_note'];
        if($data['adjustment']>0)
        {
            $journal_entry->debit_credit    = 0;
        }
        else
        {
            $journal_entry->debit_credit    = 1;
        }
        $journal_entry->amount              = abs($data['adjustment']);
        $journal_entry->account_name_id     = 18;
        $journal_entry->jurnal_type         = "invoice";
        $journal_entry->invoice_id          = $invoice_id;
        $journal_entry->contact_id          = $data['customer_id'];
        $journal_entry->created_by          = $user_id;
        $journal_entry->updated_by          = $user_id;
        $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

        if($journal_entry->save())
        {

        }
        else
        {
                //delete all journal entry for this invoice...
            $invoice = Invoice::find($invoice_id);
            $invoice->delete();
            return false;
        }
    }


        //return $account_array;
    $invoice_entry = [];
    for($j = 1; $j<count($account_array)-2; $j++) {
        if ($account_array[$j] != 0)
        {
            $invoice_entry[] = [
                'note'              => $data['customer_note'],
                'debit_credit'      => 0,
                'amount'            => $account_array[$j],
                'account_name_id'   => $j,
                'jurnal_type'       => 'invoice',
                'invoice_id'        => $invoice_id,
                'contact_id'        => $data['customer_id'],
                'created_by'        => $user_id,
                'updated_by'        => $user_id,
                'created_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                'updated_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                'assign_date'       => date('Y-m-d',strtotime($data['invoice_date'])),
            ];

        }
    }

    if (DB::table('journal_entries')->insert($invoice_entry))
        {
            return true;
        }
        else
        {
            //delete all journal entry for this invoice...
            $invoice = Invoice::find($invoice_id);
            $invoice->delete();
            return false;
        }

        return false;

    }


    public function insertAgainJournalEntries2($data,$id, $agent_commission_amount)
    {
        $user_id = Auth::user()->id;

        $i = 0;
        $discount = 0;


        //for agent commission manual journal entry
        if($data['commission_type'] && $data['agent_id'] && $data['agentcommissionAmount'])
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 1;
            if($data['commission_type'] == 1)
            {
                $journal_entry->amount = $agent_commission_amount;
            }
            else
            {
                $journal_entry->amount = $data['agentcommissionAmount'];
            }

            $journal_entry->account_name_id = 30;
            $journal_entry->jurnal_type     = "sales_commission";
            $journal_entry->invoice_id      = $id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->agent_id        = $data['agent_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));
            $journal_entry->save();

            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 0;
            if($data['commission_type'] == 1)
            {
                $journal_entry->amount = $agent_commission_amount;
            }
            else
            {
                $journal_entry->amount = $data['agentcommissionAmount'];
            }
            $journal_entry->account_name_id = 11;
            $journal_entry->jurnal_type     = "sales_commission";
            $journal_entry->invoice_id      = $id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->agent_id        = $data['agent_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));
            $journal_entry->save();
        }

        //insert total amount as debit
        $journal_entry = new JournalEntry;
        $journal_entry->note            = $data['customer_note'];
        $journal_entry->debit_credit    = 1;
        $journal_entry->amount          = $data['total_amount'];
        $journal_entry->account_name_id = 5;
        $journal_entry->jurnal_type     = "invoice";
        $journal_entry->invoice_id      = $id;
        $journal_entry->contact_id      = $data['customer_id'];
        $journal_entry->created_by      = $user_id;
        $journal_entry->updated_by      = $user_id;
        $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

        if($journal_entry->save())
        {

        }
        else
        {
            //delete all journal entry for this invoice...
            $invoice = Invoice::find($id);
            $invoice->delete();
            return false;
        }

        //insert discount as credit
        if($discount>0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 1;
            $journal_entry->amount          = $discount;
            $journal_entry->account_name_id = 21;
            $journal_entry->jurnal_type     = "invoice";
            $journal_entry->invoice_id      = $id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date     = date('Y-m-d',strtotime($data['invoice_date']));

            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
                $invoice = Invoice::find($id);
                $invoice->delete();
                return false;
            }
        }


        //insert tax total as credit
        if($data['tax_total']>0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 0;
            $journal_entry->amount          = $data['tax_total'];
            $journal_entry->account_name_id = 9;
            $journal_entry->jurnal_type     = "invoice";
            $journal_entry->invoice_id      = $id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date     = date('Y-m-d',strtotime($data['invoice_date']));

            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
                $invoice = Invoice::find($id);
                $invoice->delete();
                return false;
            }
        }

        //insert shipping charge as credit
        if($data['shipping_charge']>0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 0;
            $journal_entry->amount          = $data['shipping_charge'];
            $journal_entry->account_name_id = 20;
            $journal_entry->jurnal_type     = "invoice";
            $journal_entry->invoice_id      = $id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date     = date('Y-m-d',strtotime($data['invoice_date']));

            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
                $invoice = Invoice::find($id);
                $invoice->delete();
                return false;
            }
        }


        //insert adjustment as credit
        if($data['adjustment'] != 0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note                = $data['customer_note'];
            if($data['adjustment']>0)
            {
                $journal_entry->debit_credit    = 0;
            }
            else
            {
                $journal_entry->debit_credit    = 1;
            }
            $journal_entry->amount              = abs($data['adjustment']);
            $journal_entry->account_name_id     = 18;
            $journal_entry->jurnal_type         = "invoice";
            $journal_entry->invoice_id          = $id;
            $journal_entry->contact_id          = $data['customer_id'];
            $journal_entry->created_by          = $user_id;
            $journal_entry->updated_by          = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));
            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
                $invoice = Invoice::find($id);
                $invoice->delete();
                return false;
            }
        }

    }

    public function updateManualJournalEntries($data, $id, $agent_commission_amount) {

        $invoice_entries_delete = Invoice::find($id)->journalEntries();

        if($invoice_entries_delete->delete())
        {

        }

        $user_id = Auth::user()->id;

        //for agent commission manual journal entry
        if($data['commission_type'] && $data['agent_id'] && $data['agentcommissionAmount'])
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 1;
            if($data['commission_type'] == 1)
            {
                $journal_entry->amount = $agent_commission_amount;
            }
            else
            {
                $journal_entry->amount = $data['agentcommissionAmount'];
            }
            $journal_entry->account_name_id = 30;
            $journal_entry->jurnal_type     = "sales_commission";
            $journal_entry->invoice_id      = $id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->agent_id        = $data['agent_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));
            $journal_entry->save();

            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 0;
            if($data['commission_type'] == 1)
            {
                $journal_entry->amount = $agent_commission_amount;
            }
            else
            {
                $journal_entry->amount = $data['agentcommissionAmount'];
            }
            $journal_entry->account_name_id = 11;
            $journal_entry->jurnal_type     = "sales_commission";
            $journal_entry->invoice_id      = $id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->agent_id        = $data['agent_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));
            $journal_entry->save();
        }


        $i = 0;
        $discount = 0;
        $account_array = array_fill(1, 100, 0);
        foreach ($data['item_id'] as $account)
        {

            if($data['discount'][$i] == "")
            {
                $amount = $data['quantity'][$i]*$data['rate'][$i];
                $discount = $discount + (0*$amount)/100;
                $discount1 = ($data['discount'][$i]*$amount)/100;
            }
            else
            {
                $amount = $data['quantity'][$i]*$data['rate'][$i];
                
                if($data['discount_type'][$i] == 1){
                    $discount = $discount+($data['discount'][$i] * $data['quantity'][$i]);
                }else{
                    $discount = $discount + ($data['discount'][$i]*$amount)/100;
                }

                $discount1 = ($data['discount'][$i]*$amount)/100;
            }

            // $account_array[$data['account_id'][$i]] =  $account_array[$data['account_id'][$i]] + ($data['quantity'][$i]*$data['rate'][$i])-$discount1;
            $account_array[$data['account_id'][$i]] =  $account_array[$data['account_id'][$i]] + ($data['quantity'][$i]*$data['rate'][$i]);

            $i++;
        }

        // $invoice = Invoice::orderBy('created_at', 'desc')->first();
        // $invoice_id = $invoice['id'];
        $invoice_id = $id;

        //insert total amount as debit
        $journal_entry = new JournalEntry;
        $journal_entry->note            = $data['customer_note'];
        $journal_entry->debit_credit    = 1;
        $journal_entry->amount          = $data['total_amount'];
        $journal_entry->account_name_id = 5;
        $journal_entry->jurnal_type     = "invoice";
        $journal_entry->invoice_id      = $invoice_id;
        $journal_entry->contact_id      = $data['customer_id'];
        $journal_entry->created_by      = $user_id;
        $journal_entry->updated_by      = $user_id;
        $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

        if($journal_entry->save())
        {

        }
        else
        {
            //delete all journal entry for this invoice...
        }

        //insert discount as credit
        if($discount>0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 1;
            $journal_entry->amount          = $discount;
            $journal_entry->account_name_id = 21;
            $journal_entry->jurnal_type     = "invoice";
            $journal_entry->invoice_id      = $invoice_id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
            }
        }
        

        //insert tax total as debit
        if($data['tax_total']>0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 0;
            $journal_entry->amount          = $data['tax_total'];
            $journal_entry->account_name_id = 9;
            $journal_entry->jurnal_type     = "invoice";
            $journal_entry->invoice_id      = $invoice_id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
            }
        }
        

        //insert shipping charge as credit
        if($data['shipping_charge']>0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            $journal_entry->debit_credit    = 0;
            $journal_entry->amount          = $data['shipping_charge'];
            $journal_entry->account_name_id = 20;
            $journal_entry->jurnal_type     = "invoice";
            $journal_entry->invoice_id      = $invoice_id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
            }
        }
        

        //insert adjustment as credit
        if($data['adjustment'] != 0)
        {
            $journal_entry = new JournalEntry;
            $journal_entry->note            = $data['customer_note'];
            if($data['adjustment']>0)
            {
                $journal_entry->debit_credit    = 0;
            }
            else
            {
                $journal_entry->debit_credit    = 1;
            }
            $journal_entry->amount          = abs($data['adjustment']);
            $journal_entry->account_name_id = 18;
            $journal_entry->jurnal_type     = "invoice";
            $journal_entry->invoice_id      = $invoice_id;
            $journal_entry->contact_id      = $data['customer_id'];
            $journal_entry->created_by      = $user_id;
            $journal_entry->updated_by      = $user_id;
            $journal_entry->assign_date      = date('Y-m-d',strtotime($data['invoice_date']));

            if($journal_entry->save())
            {

            }
            else
            {
                //delete all journal entry for this invoice...
            }
        }
        

        //return $account_array;

        $invoice_entry = [];
        for($j = 1; $j<count($account_array)-2; $j++) {
            if ($account_array[$j] != 0)
            {
                $invoice_entry[] = [
                    'note'              => $data['customer_note'],
                    'debit_credit'      => 0,
                    'amount'            => $account_array[$j],
                    'account_name_id'   => $j,
                    'jurnal_type'       => 'invoice',
                    'invoice_id'        => $invoice_id,
                    'contact_id'        => $data['customer_id'],
                    'created_by'        => $user_id,
                    'updated_by'        => $user_id,
                    'created_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                    'assign_date'      =>date('Y-m-d',strtotime($data['invoice_date'])),
                ];

            }
        }

        if (DB::table('journal_entries')->insert($invoice_entry))
            {
                return "successfull...";
            }
            else
            {
            //delete all journal entry for this invoice...
            }

            return "error";
        }

        public function useCredit(Request $request)
        {
            $data = $request->all();
            $i = 0;
            foreach ($data['credit_amount'] as $credit_amount)
            {
                if($credit_amount)
                {
                    $credit_note = CreditNote::find($data['credit_note_id'][$i]);
                    $credit_note->available_credit = ($credit_note['available_credit'] - $credit_amount);
                    $credit_note->update();

                    $invoice = Invoice::find($data['invoice_id']);
                    $invoice->due_amount = $invoice['due_amount'] - $credit_amount;
                    $invoice->update();

                }
                $i++;
            }
            $user_id = Auth::user()->id;

            $credit_note_payment_entry = [];
            for($i = 0; $i < count($data['credit_amount']); $i++) {
                if (!$data['credit_amount'][$i])
                {
                    continue;
                }

                $credit_note_payment_entry[] = [
                    'amount'            => $data['credit_amount'][$i],
                    'invoice_id'        => $data['invoice_id'],
                    'credit_note_id'    => $data['credit_note_id'][$i],
                    'created_by'        => $user_id,
                    'updated_by'        => $user_id,
                    'created_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                ];
            }

            if (DB::table('credit_note_payments')->insert($credit_note_payment_entry))
                {
                    return redirect()
                    ->route('invoice_show', ['id' => $data['invoice_id']])
                    ->with('alert.status', 'success')
                    ->with('alert.message', 'Credit notes used successfully!');
                }

                return redirect()
                ->route('invoice_show', ['id' => $data['invoice_id']])
                ->with('alert.status', 'danger')
                ->with('alert.message', 'Something went to wrong! please check your input field!!!');
            }

            public function useExcessPayment(Request $request)
            {
                $data = $request->all();
        //return $data;
                $user_id = Auth::user()->id;
                $helper = new \App\Lib\Helpers;
                $i = 0;
                foreach ($data['excess_payment_amount'] as $excess_payment_amount)
                {
                    if($excess_payment_amount)
                    {
                        $helper->updatePaymentReceiveEntryAfterExcessAmountUse($data['invoice_id'], $data['payment_receive_id'][$i], $excess_payment_amount, $user_id);

                        $payment_receive = PaymentReceives::find($data['payment_receive_id'][$i]);
                        $payment_receive->excess_payment = ($payment_receive['excess_payment'] - $excess_payment_amount);
                        $payment_receive->update();

                        $invoice = Invoice::find($data['invoice_id']);
                        $invoice->due_amount = $invoice['due_amount'] - $excess_payment_amount;
                        $invoice->update();
                    }
                    $i++;
                }


                $i = 0;
                foreach ($data['excess_payment_amount'] as $excess_payment_amount)
                {
                    if($excess_payment_amount)
                    {
                        $helper->addOrUpdateJournalEntry($data['invoice_id'], $data['payment_receive_id'][$i], $excess_payment_amount, $user_id);
                    }
                    $i++;
                }

                return redirect()
                ->route('invoice_show', ['id' => $data['invoice_id']])
                ->with('alert.status', 'success')
                ->with('alert.message', 'Excess notes used successfully!');
            }

            public function download($attachment)
            {
                $download_link = public_path('uploads/invoice/'.$attachment);
                return response()->download($download_link);
            }


            public function challan($id)
            {
                $invoice = Invoice::find($id);
                $payment_receive_entries = PaymentReceiveEntryModel::where('invoice_id', $id)->get();
                $credit_receive_entries = CreditNotePayment::where('invoice_id', $id)->get();
                $excess_receive_entries = ExcessPayment::where('invoice_id', $id)->get();
                $invoices = Invoice::all();
                $invoice_entries = InvoiceEntry::where('invoice_id', $id)->get();
                $sub_total = 0;
                $OrganizationProfile = OrganizationProfile::find(1);
                foreach ($invoice_entries as $invoice_entry)
                {
                    $sub_total = $sub_total + $invoice_entry->amount;
                }

                return view('invoice::invoice.challan', compact('invoice', 'invoice_entries', 'sub_total','invoices','payment_receive_entries','credit_receive_entries','excess_receive_entries','OrganizationProfile'));
            }

            public function challanUpdate(Request $request,$id)
            {
                $input = $request->all();
                $invoice_entries = InvoiceEntry::where('invoice_id', $id)->get();
        //dd($invoice_entries[0]);
                for($i=0;$i<count($invoice_entries);$i++)
                {
                    $invoice_entries[$i]->carton        = $input['carton'][$i];
                    $invoice_entries[$i]->remarks       = $input['remarks'][$i];
                    $invoice_entries[$i]->update();
                }

                return back()->with('challan_msg' , 'Challan Updated Successfully');
            }

            public function insertAgainJournalEntries($data,$agent_commission_amount)
            {
                try{

                    $user_id = Auth::user()->id;
                    $discount = InvoiceEntry::where('invoice_id',$data->id)->select(DB::raw('sum(((rate*quantity)*discount)/100) as discount'))->first();
                    $amount = InvoiceEntry::where('invoice_id',$data->id)->select(DB::raw('sum(rate*quantity) as amount,invoice_entries.account_id'))->groupBy('invoice_entries.account_id')->first();


                    if(isset($discount->discount)){
                        $discount=  $discount->discount;
                    }else{
                        $discount=0;
                    }
                    if($data->commission_type && $data->agents_id && $data->agentcommissionAmount) {


                        $journal_entry = new JournalEntry;
                        $journal_entry->note = $data->customer_note;
                        $journal_entry->debit_credit = 1;
                // $journal_entry->amount = $data->agentcommissionAmount;
                        if ($data->commission_type == 1) {
                            $journal_entry->amount = $agent_commission_amount;
                        } else {
                            $journal_entry->amount = $data->agentcommissionAmount;
                        }

                        $journal_entry->account_name_id = 30;
                        $journal_entry->assign_date =date('Y-m-d',strtotime($data->invoice_date));
                        $journal_entry->jurnal_type = "sales_commission";
                        $journal_entry->invoice_id = $data->id;
                        $journal_entry->contact_id = $data->customer_id;
                        $journal_entry->agent_id = $data->agents_id;
                        $journal_entry->created_by = $user_id;
                        $journal_entry->updated_by = $user_id;
                        $journal_entry->save();

                        $journal_entry = new JournalEntry;
                        $journal_entry->note = $data->customer_note;
                        $journal_entry->debit_credit = 0;

                // $journal_entry->amount = $data->agentcommissionAmount;
                        if ($data->commission_type == 1) {
                            $journal_entry->amount = $agent_commission_amount;
                        } else {
                            $journal_entry->amount = $data->agentcommissionAmount;
                        }
                        $journal_entry->assign_date = date('Y-m-d',strtotime($data->invoice_date));
                        $journal_entry->account_name_id = 11;
                        $journal_entry->jurnal_type     = "sales_commission";
                        $journal_entry->invoice_id      =  $data->id;
                        $journal_entry->contact_id      = $data->customer_id;
                        $journal_entry->agent_id        = $data->agents_id;
                        $journal_entry->created_by      = $user_id;
                        $journal_entry->updated_by      = $user_id;
                        $journal_entry->save();

                    }



            //insert total amount as debit
                    $journal_entry = new JournalEntry;
                    $journal_entry->assign_date = date('Y-m-d',strtotime($data->invoice_date));
                    $journal_entry->note  = $data->customer_note;
                    $journal_entry->debit_credit    = 1;
                    $journal_entry->amount          = $data->total_amount;
                    $journal_entry->account_name_id = 5;
                    $journal_entry->jurnal_type     = "invoice";
                    $journal_entry->invoice_id      =  $data->id;
                    $journal_entry->contact_id      = $data->customer_id;
                    $journal_entry->created_by      = $user_id;
                    $journal_entry->updated_by      = $user_id;
                    $journal_entry->save();

            //insert discount as credit
                    if($discount>0)
                    {
                        $journal_entry = new JournalEntry;
                        $journal_entry->assign_date = date('Y-m-d',strtotime($data->invoice_date));
                        $journal_entry->note  = $data->customer_note;
                        $journal_entry->debit_credit    = 1;
                        $journal_entry->amount          = $discount;
                        $journal_entry->account_name_id = 21;
                        $journal_entry->jurnal_type     = "invoice";
                        $journal_entry->invoice_id      = $data->id;
                        $journal_entry->contact_id      = $data->customer_id;
                        $journal_entry->created_by      = $user_id;
                        $journal_entry->updated_by      = $user_id;
                        $journal_entry->save();

                    }


            //insert tax total as credit
                    if($data->tax_total>0)
                    {
                        $journal_entry = new JournalEntry;
                        $journal_entry->assign_date = date('Y-m-d',strtotime($data->invoice_date));
                        $journal_entry->note  = $data->customer_note;
                        $journal_entry->debit_credit    = 0;
                        $journal_entry->amount          =$data->tax_total;
                        $journal_entry->account_name_id = 9;
                        $journal_entry->jurnal_type     = "invoice";
                        $journal_entry->invoice_id      = $data->id;
                        $journal_entry->contact_id      = $data->customer_id;
                        $journal_entry->created_by      = $user_id;
                        $journal_entry->updated_by      = $user_id;
                        $journal_entry->save();

                    }


            //insert shipping charge as credit
                    if($data->shipping_charge>0)
                    {
                        $journal_entry = new JournalEntry;
                        $journal_entry->assign_date = date('Y-m-d',strtotime($data->invoice_date));
                        $journal_entry->note  = $data->customer_note;
                        $journal_entry->debit_credit    = 0;
                        $journal_entry->amount          = $data->shipping_charge;
                        $journal_entry->account_name_id = 20;
                        $journal_entry->jurnal_type     = "invoice";
                        $journal_entry->invoice_id      = $data->id;
                        $journal_entry->contact_id      = $data->customer_id;
                        $journal_entry->created_by      = $user_id;
                        $journal_entry->updated_by      = $user_id;
                        $journal_entry->save();

                    }


            //insert adjustment as credit
                    if($data->adjustment != 0)
                    {
                        $journal_entry = new JournalEntry;
                        $journal_entry->assign_date = date('Y-m-d',strtotime($data->invoice_date));
                        $journal_entry->note  = $data->customer_note;
                        if($data->adjustment>0)
                        {
                            $journal_entry->debit_credit    = 0;
                        }
                        else
                        {
                            $journal_entry->debit_credit    = 1;
                        }
                        $journal_entry->amount              = abs($data->adjustment);
                        $journal_entry->account_name_id     = 18;
                        $journal_entry->jurnal_type         = "invoice";
                        $journal_entry->invoice_id      = $data->id;
                        $journal_entry->contact_id      = $data->customer_id;
                        $journal_entry->created_by          = $user_id;
                        $journal_entry->updated_by          = $user_id;
                        $journal_entry->save();

                    }



                    $invoice_entry = [];

                    $invoice_entry[] = [
                        'note'              => $data->customer_note,
                        'debit_credit'      => 0,
                        'amount'            => $amount->amount,
                        'account_name_id'   => $amount->account_id,
                        'jurnal_type'       => 'invoice',
                        'invoice_id'        => $data->id,
                        'contact_id'        => $data->customer_id,
                        'created_by'        => $user_id,
                        'assign_date'        => date('Y-m-d',strtotime($data->invoice_date)),
                        'updated_by'        => $user_id,
                        'created_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                        'updated_at'        => \Carbon\Carbon::now()->toDateTimeString(),
                    ];
            //dd($invoice_entry);

                    DB::table('journal_entries')->insert($invoice_entry);


                }catch (\Exception $exception){
                    dd($exception);
                }

            }


            public function saveUpdate(Request $request, $id){


                $invoice=Invoice::find($id);

                $helper = new \App\Lib\Helpers;

                if ($invoice->commission_type == 1) {
                    $agent_commission_amount = ($invoice->agentcommissionAmount / 100) * $invoice->total_amount;
                } else {
                    $agent_commission_amount = $invoice->agentcommissionAmount;
                }


                $status1 = $helper->checkItemStock($invoice);

                if ($status1){

                    return response(['status'=>(string)$status1,$invoice]) ;

                }else{

                    $status1= $this->insertAgainJournalEntries($invoice,$agent_commission_amount);
                    $helper->updateItemAfterCreatingInvoice2($invoice);
                    $invoice=Invoice::find($id);
                    $invoice->save=null;
                    $invoice->save();
                    return response(['status'=>(string)$status1,$invoice]) ;

                }

            }


            public function showStock($id){

                $invoice=Invoice::join('invoice_entries', 'invoices.id', '=', 'invoice_entries.invoice_id')
                ->join('item', 'item.id', '=', 'invoice_entries.item_id')
                ->select(DB::raw('item.item_name ,item.total_purchases,item.total_sales,sum(invoice_entries.quantity) as quantity'))
                ->where('invoice_entries.invoice_id', '=',$id)
                ->groupBy('invoice_entries.item_id')
                ->get();

                $row='';

                foreach ($invoice as $item){

                    $stock = ($item->total_purchases - $item->total_sales);
                    $row.='<tr>';

                    $row.='<td>'.$item->item_name.'</td>';
                    $row.='<td>'.$stock.'</td>';
                    $row.='<td>'.$item->quantity.'</td>';

                    $row.='</tr>';
                }

                return response($row);
            }

            public function addStock(Request $request,$id){

                $invoice=Invoice::join('invoice_entries', 'invoices.id', '=', 'invoice_entries.invoice_id')
                ->join('item', 'item.id', '=', 'invoice_entries.item_id')
                ->select(DB::raw('item.id,item.item_category_id,item.created_by,item.updated_by,item.branch_id,item.total_purchases,item.total_sales,sum(invoice_entries.quantity) as quantity'))
                ->where('invoice_entries.invoice_id', '=',$id)
                ->groupBy('invoice_entries.item_id')
                ->get();


                foreach ($invoice as $item){

                    $st =($item->quantity)-($item->total_purchases - $item->total_sales);

                    $stock=new Stock();
                    $stock->total=$st;
                    $stock->item_category_id=$item->item_category_id;
                    $stock->item_id=$item->id;
                    $stock->branch_id=$item->branch_id;
                    $stock->created_by=$item->created_by;
                    $stock->updated_by=$item->updated_by;
                    $stock->save();

                    if ($stock){
                        $item2=Item::find($item->id);
                        $item2->total_purchases=$item->total_purchases+$st;
                        $item2->save();
                    }
                }

                return redirect()->back()->with('msg','stock added successfully');

            }




            public function dueDate(Request $request, $id)
            {
                $all_data = $request->toArray();
                // dd($all_data);
                $count = count($all_data[0]);
                
                $old_data = InvoiceDueDates::where('invoice_id', '=', $id)->get();
                $data_count = count($old_data);

                if ($data_count > 0)
                {
                   for ($i=0; $i < $data_count ; $i++) 
                    { 
                        $old_due_date_delete = InvoiceDueDates::find($old_data[$i]->id);
                        $old_due_date_delete->delete();
                    }
                  for ($i=0; $i < $count ; $i++) 
                    { 
                        $due_date = new InvoiceDueDates();
                        $due_date-> invoice_id = $id;
                        $due_date-> date = $all_data[0][$i];        //0 for date
                        $due_date-> amount = $all_data[1][$i];     //1 for amount
                        $due_date-> created_by = Auth::user()->id;
                        $due_date->save();
                    }

                }else{
                    for ($i=0; $i < $count ; $i++) 
                        { 
                            $due_date = new InvoiceDueDates();
                            $due_date-> invoice_id = $id;
                            $due_date-> date = $all_data[0][$i];        //0 for date
                            $due_date-> amount = $all_data[1][$i];     //1 for amount
                            $due_date-> created_by = Auth::user()->id;
                            $due_date->save();
                        }
                }
                return redirect()->back()->with('successAlert', 'You have successfully added !!');      
            }




        }