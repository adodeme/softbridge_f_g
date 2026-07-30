<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user->role === 'administrateur') {
            return response()->json(Invoice::with('client.user', 'project')->orderBy('created_at', 'desc')->get());
        }
        return response()->json($user->client->invoices()->with('client.user', 'project')->get());
    }

    public function show($id)
    {
        return response()->json(Invoice::with('client.user', 'project')->findOrFail($id));
    }

    public function downloadPdf($id)
    {
        $invoice = Invoice::with('client.user', 'project')->findOrFail($id);
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        return $pdf->download('facture_' . $invoice->numero . '.pdf');
    }
    public function all()
    {
        return response()->json(
            Invoice::with('client.user', 'project')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }
}