<?php

namespace App\Http\Controllers\API;

use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\TransactionItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function all(Request $request) {
        $id = $request->input('id');
        $limit = $request->input('limit');
        $status = $request->input('status');

        if ($id) {
            $transaction = Transaction::with(['items.product'])->find('id');

            if($transaction) {
                return ResponseFormatter::success($transaction, 'Data Transaksi Berhasil Diambil');
            } //if transaction
            else {
                return ResponseFormatter::error(null, 'Data Transaksi Gagal diambil', 404); // return error
            } // else
        } // if (id)
        $transaction = Transaction::with(['items.product'])->where('user_id', Auth::user()->id);

        if ($status) {
            $transaction->transaction('status', $status);
        }
        return ResponseFormatter::success($transaction->paginate($limit), 'Data List Transaksi berhasil diambil');
    } 

    public function checkout (Request $request) {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'exists:product, id',
            'total_price' => 'required',
            'shipping_price' => 'required',
            'status' => 'required|in:PENDING, SUCCESS, CANCELED, FAILED, SHIPPING, SHIPPED'
        ]);
        $transaction = Transaction::create([
            'users_id' => Auth::user()->id,
            'address' => $request->address,
            'total_price' => $request->total_price,
            'shipping_price' => $request->shipping_price,
            'status' => $request->status
        ]);
        foreach ($request->items as $product) {
            TransactionItem::create([
                'users_id' => Auth::user()->id,
                'products_id' => $product['id'],
                'transaction_id' => $request->transaction->id,
                'quantity' => $product['quantity']
            ]);
        }
        return ResponseFormatter::success($transaction->load('items.product'),'Transaksi Berhasil');
    }
}
