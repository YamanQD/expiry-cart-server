<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Comment;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List all products.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $products = Product::all();
        // Delete expired products
        foreach ($products as $product) {
            if (date_diff(date_create('now'), date_create($product->expiry_date))->invert) {
                // Delete image file
                if ($product->image) {
                    $imageFile = public_path('images/products/' . $product->image);
                    if (file_exists($imageFile)) {
                        unlink($imageFile);
                    }
                }
                $product->delete();
            }
        }

        // Filter products by category
        $category = $request->input('category');
        if ($category) {
            // Check if category exists
            $category = Category::where('name', $category)->first();
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            return $category->products->map(function ($product) {
                $owner = $product->user;
                $owner = [
                    'id' => $owner->id,
                    'name' => $owner->name,
                ];

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'image' => $product->image,
                    'category' => $product->category->name,
                    'expiry_date' => $product->expiry_date,
                    'votes' => $product->votes,
                    'views' => $product->views,
                    'owner' => $owner,
                ];
            });
        }

        // Return all products
        return Product::all()->map(function ($product) {
            $owner = $product->user;
            $owner = [
                'id' => $owner->id,
                'name' => $owner->name,
            ];

            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image,
                'category' => $product->category->name,
                'expiry_date' => $product->expiry_date,
                'votes' => $product->votes,
                'views' => $product->views,
                'owner' => $owner,
            ];
        });
    }

    /**
     * Create a product.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'description' => 'string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'numeric|min:0',
            'contact_info' => 'required|string',
            'expiry_date' => 'required|date|after:today',
            'thirty_days_discount' => 'required|numeric|min:0|max:100',
            'fifteen_days_discount' => 'required|numeric|min:0|max:100',
            'image' => 'image',
            'category' => 'required|string',
        ]);

        // Check if image size is less than 512KB
        if ($request->hasFile('image') && $request->file('image')->getSize() > (0.5 * 1024 * 1024)) {
            return response()->json(['error' => 'Image size must be less than 512KB'], 400);
        }

        // Check if category exists
        foreach (Category::all() as $key => $value) {
            if ($value->name === $request->input('category')) {
                $fields['category_id'] = $value->id;
            }
        }
        if (!isset($fields['category_id'])) {
            return response()->json(['error' => 'Category does not exist'], 400);
        }

        $user = $request->user();
        $fields['user_id'] = $user->id;

        $product = Product::create($fields);
        return response($product, 201);
    }

    /**
     * Get a product by id.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Increase product views
        $product->views += 1;
        $product->save();

        $owner = $product->user;
        $owner = [
            'id' => $owner->id,
            'name' => $owner->name,
        ];

        $comments = $product->comments->map(function ($comment) {
            $owner = $comment->user;
            $owner = [
                'id' => $owner->id,
                'name' => $owner->name,
            ];

            return [
                'id' => $comment->id,
                'body' => $comment->body,
                'owner' => $owner,
            ];
        });

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'image' => $product->image,
            'category' => $product->category->name,
            'expiry_date' => $product->expiry_date,
            'description' => $product->description,
            'contact_info' => $product->contact_info,
            'votes' => $product->votes,
            'views' => $product->views,
            'owner' => $owner,
            'comments' => $comments,
        ], 200);
    }

    /**
     * Update a product by id.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Check if product exists
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Check if user is owner of product
        $user = $request->user();
        if ($user->id !== $product->user_id) {
            return response()->json(['error' => 'You are not the owner of this product'], 403);
        }

        $fields = $request->validate([
            'description' => 'string',
            'quantity' => 'numeric|min:0',
            'contact_info' => 'string',
        ]);

        $product->update($fields);
        $product->save();

        return response($product, 200);
    }

    /**
     * Delete a product by id.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        // Check if product exists
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Check if user is owner of product
        $user = $request->user();
        if ($user->id != $product->user_id) {
            return response()->json(['error' => 'You are not the owner of this product'], 403);
        }

        // Delete product image from server
        if ($product->image) {
            $imageFile = public_path('images/products/' . $product->image);
            if (file_exists($imageFile)) {
                unlink($imageFile);
            }
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully'], 200);
    }

    /**
     * Vote for a product.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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

    /**
     * Comment on a product.
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function comment(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $fields = $request->validate([
            'body' => ['required', 'string', 'max:255'],
        ]);

        $comment = Comment::create([
            'body' => $fields['body'],
            'user_id' => $request->user()->id,
            'product_id' => $id,
        ]);

        return response($comment, 201);
    }
}
