<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Vote;
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

            $products = $category->products->map(function ($product) {
                // Add owner field with id and name
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
        } else {
            // Return all products
            $products = Product::all()->map(function ($product) {
                // Add owner field with id and name
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
                ];
            });
        }

        $searchTerm = $request->input('search');
        if ($searchTerm) {
            $products = $products->filter(function ($product) use ($searchTerm) {
                return strpos(strtolower($product['name']), strtolower($searchTerm)) !== false ||
                    strpos(strtolower($product['expiry_date']), strtolower($searchTerm)) !== false;
            });
            $products = $products->values();
        }

        $sortBy = $request->input('sort');
        if ($sortBy == 'price' || $sortBy == 'expiry_date' || $sortBy == 'name') {
            $products = $products->sortBy($sortBy);
            $products = $products->values();
        }

        return response()->json($products);
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

        // Check if category exists
        foreach (Category::all() as $key => $value) {
            if ($value->name === $request->input('category')) {
                $fields['category_id'] = $value->id;
            }
        }
        if (!isset($fields['category_id'])) {
            return response()->json(['error' => 'Category does not exist'], 400);
        }

        if (!$request->hasFile('image')) {
            $fields['image'] = 'default.png';
        }

        $user = $request->user();
        $fields['user_id'] = $user->id;

        Product::create($fields);
        return response()->json(['success' => 'Product created successfully'], 201);
    }

    /**
     * Get a product by id.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Increase product views
        $product->views += 1;
        $product->save();


        $isOwner = $request->user()->id === $product->user->id;

        $comments = $product->comments->map(function ($comment) {
            // Add owner field with id and name
            $owner = $comment->user->name;

            return [
                'id' => $comment->id,
                'body' => $comment->body,
                'owner' => $owner,
            ];
        });

        $user = $request->user();
        $userVote = $product->votes()->where('user_id', $user->id)->first();
        if ($userVote) {
            $userVote = $userVote->type == 'up' ? 1 : -1;
        } else {
            $userVote = 0;
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'image' => $product->image,
            'category' => $product->category->name,
            'expiry_date' => $product->expiry_date,
            'description' => $product->description,
            'contact_info' => $product->contact_info,
            'votes' => $product->votes,
            'views' => $product->views,
            'is_owner' => $isOwner,
            'user_vote' => $userVote,
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
        if ($user->id != $product->user_id) {
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
        if ($product->image && $product->image !== 'default.png') {
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
            'type' => 'required|string|in:up,down',
        ]);

        $product = Product::find($id);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $user = $request->user();
        $vote = $user->votes()->where('product_id', $id)->first();
        // If user already voted for this product
        if ($vote) {
            // If user already voted with the same vote
            if ($vote->type === $fields['type']) {
                return response()->json(['error' => 'You have already voted for this product'], 400);
            }

            // If user already voted with different vote
            $vote->type = $fields['type'];
            $vote->save();
            $product->votes += $fields['type'] === 'up' ? 2 : -2;
            $product->save();
            return response()->json(['success' => 'Vote updated successfully'], 200);
        }
        // If user hasn't voted for this product
        $user->votes()->create([
            'product_id' => $id,
            'type' => $fields['type'],
        ]);
        $product->votes += $fields['type'] === 'up' ? 1 : -1;
        $product->save();

        return response()->json(['success' => 'Voted successfully'], 200);
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
            'body' => 'required|string|max:255',
        ]);

        $comment = Comment::create([
            'body' => $fields['body'],
            'user_id' => $request->user()->id,
            'product_id' => $id,
        ]);

        return response($comment, 201);
    }
}
