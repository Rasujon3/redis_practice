<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Exception;
use DataTables;
use Log;

class ProductRedisController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth_check');
    }

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                // সব প্রোডাক্ট লোড
                $ids = Redis::lrange('products', 0, -1);
                $products = [];

                foreach ($ids as $id) {
                    $data = Redis::get("product:{$id}");
                    if ($data) {
                        $products[] = json_decode($data, true);
                    }
                }

                // Datatables এর জন্য কালেকশন বানানো
                return Datatables::of(collect($products))
                    ->addIndexColumn()
                    ->addColumn('action', function ($row) {
                        $btn = '<a href="' . route('redisProducts.show', $row['id']) . '" class="btn btn-primary btn-sm"><i class="fa fa-edit"></i></a>';
                        $btn .= ' <a href="#" class="btn btn-danger btn-sm delete-product" data-id="' . $row['id'] . '"><i class="fa fa-trash"></i></a>';
                        return $btn;
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('admin.redisProducts.index');
        } catch (Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function create()
    {
        return view('admin.redisProducts.create');
    }

    public function store(Request $request)
    {
        try {
            // ফাইল আপলোড
            $path = '';
            if ($request->hasFile('file')) {
                $path = $this->storeFile($request->file('file'));
            }

            // নতুন ID জেনারেট
            $id = uniqid();

            $product = [
                'id' => $id,
                'name' => $request->name,
                'price' => $request->price,
                'description' => $request->description,
                'commission' => $request->commission,
                'file' => $path,
                'created_at' => now()->toDateTimeString(),
            ];

            // Redis এ সংরক্ষণ
            Redis::set("product:{$id}", json_encode($product));
            Redis::rpush('products', $id);

            return redirect()->route('redisProducts.index')->with([
                'message' => 'Successfully a product has been added',
                'alert-type' => 'success'
            ]);
        } catch (Exception $e) {
            Log::error('Redis store error', ['error' => $e->getMessage()]);
            return back()->with([
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            ]);
        }
    }

    public function show($id)
    {
        $product = Redis::get("product:{$id}");
        if (!$product) {
            abort(404);
        }
//        dd('$product', json_decode($product, true));
        return view('admin.redisProducts.edit', ['product' => json_decode($product, true)]);
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Redis::get("product:{$id}");
            if (!$product) {
                abort(404);
            }

            $productData = json_decode($product, true);

            // ফাইল আপডেট
            $path = $productData['file'] ?? '';
            if ($request->hasFile('file')) {
                $path = $this->updateFile($request->file('file'), $productData);
            }

            // ডাটা আপডেট
            $productData['name'] = $request->name ?? $productData['name'];
            $productData['price'] = $request->price ?? $productData['price'];
            $productData['description'] = $request->description ?? $productData['description'];
            $productData['commission'] = $request->commission ?? $productData['commission'];
            $productData['file'] = $path;

            Redis::set("product:{$id}", json_encode($productData));

            return redirect()->route('redisProducts.index')->with([
                'message' => 'Successfully the product has been updated',
                'alert-type' => 'success'
            ]);
        } catch (Exception $e) {
            Log::error('Redis update error', ['error' => $e->getMessage()]);
            return back()->with([
                'message' => 'Something went wrong!!!',
                'alert-type' => 'error'
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Redis::get("product:{$id}");
            if (!$product) {
                return response()->json(['status' => false, 'message' => 'Not found']);
            }

            $productData = json_decode($product, true);

            // ফাইল ডিলিট
            $this->deleteOldFile($productData);

            // Redis থেকে মুছে ফেলা
            Redis::del("product:{$id}");
            Redis::lrem('products', 0, $id);

            return response()->json(['status' => true, 'message' => 'Successfully deleted']);
        } catch (Exception $e) {
            Log::error('Redis delete error', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Error occurred']);
        }
    }

    // ফাইল হ্যান্ডলিং ফাংশন গুলো আগের মতোই থাকবে
    private function storeFile($file)
    {
        $filePath = 'uploads/redisProducts';
        $directory = public_path($filePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        $fileName = uniqid('product_', true) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $fileName);
        return $filePath . '/' . $fileName;
    }

    private function updateFile($file, $data)
    {
        $filePath = 'uploads/redisProducts';
        $directory = public_path($filePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }
        $fileName = uniqid('product_', true) . '.' . $file->getClientOriginalExtension();
        $this->deleteOldFile($data);
        $file->move($directory, $fileName);
        return $filePath . '/' . $fileName;
    }

    private function deleteOldFile($data)
    {
        if (!empty($data['file'])) {
            $oldFilePath = public_path($data['file']);
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
    }
}
