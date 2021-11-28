<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::all();

        foreach ($products as $product) { // Delete expired products
            if (date_diff(date_create('now'), date_create($product->expiry_date))->invert) {
                if ($product->image) { // Delete image file
                    $imageFile = public_path('images/products/' . $product->image);
                    if (file_exists($imageFile)) {
                        unlink($imageFile);
                    }
                }
                $product->delete();
            }
        }
        return Product::all();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['string'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['numeric', 'min:0'],
            'contact_info' => ['required', 'string'],
            'expiry_date' => ['required', 'date'],
            'thirty_days_discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'fifteen_days_discount' => ['required', 'numeric', 'min:0', 'max:100'],
            'image' => ['image'],
        ]);

        if ($request->hasFile('image') && $request->file('image')->getSize() > (0.5 * 1024 * 1024)) {
            return response()->json(['error' => 'Image size must be less than 512KB'], 400);
        }

        $product = Product::create($fields);
        return response($product, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::find($id);
        $product->views += 1;
        $product->save();

        return $product ?
            response($product) :
            response()->json(['error' => 'Product not found'], 404);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $fields = $request->validate([
            'name' => ['string'],
            'description' => ['string'],
            'price' => ['numeric', 'min:0'],
            'quantity' => ['numeric', 'min:0'],
            'contact_info' => ['string'],
            'expiry_date' => ['date'],
            'thirty_days_discount' => ['numeric', 'min:0', 'max:100'],
            'fifteen_days_discount' => ['numeric', 'min:0', 'max:100'],
            'image' => ['image'],
        ]);
        if ($request->hasFile('image') && $request->file('image')->getSize() > (0.5 * 1024 * 1024)) {
            return response()->json(['error' => 'Image size must be less than 512KB'], 400);
        }

        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $product->update($fields);
        $product->save();

        return response($product, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        if ($product->image) {
            $imageFile = public_path('images/products/' . $product->image);
            if (file_exists($imageFile)) {
                unlink($imageFile);
            }
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    public function vote(Request $request, $id)
    {
        $fields = $request->validate([
            'vote' => ['required', 'numeric', 'min:-1', 'max:1'],
        ]);

        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $product->votes += $fields['vote'];
        $product->save();

        return response()->json(['success' => 'Vote added successfully'], 200);
    }
}
