<?php

namespace App\Http\Controllers;
use App\Product;
use App\Category;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class NoneAddedProductController extends Controller
{
    public function index(Request $request)
    {
    
    $products = Product::join('categories', 'products.category_id', '=', 'categories.id')
        ->where('categories.name', 'Not Added Products')
        ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.category_id',
                'categories.name as category_name',
                'products.created_at',
                'products.updated_at'
            ])
            ->get();

            return view('product.none_added',compact('products'));
    }

    /**
     * Log activity when user clicks to go to products list
     */
    public function logProductsListAccess(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');
            $product_name = $request->input('product_name', 'Unknown Product');

            // Log the activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn(new Product())
                ->withProperties([
                    'product_name' => $product_name,
                    'action' => 'navigated_to_products_list',
                    'source_page' => 'none_added_products'
                ])
                ->log('navigated_to_products_list_from_none_added');

            return response()->json([
                'success' => true,
                'message' => 'Activity logged successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error logging activity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error logging activity'
            ], 500);
        }
    }

    /**
     * Remove product from "Not Added Products" category and log activity
     */
    public function removeFromNoneAdded(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');
            $product_name = $request->input('product_name', 'Unknown Product');
            $product_id = $request->input('product_id');

            // Find the product
            $product = Product::where('business_id', $business_id)
                            ->where('id', $product_id)
                            ->first();

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Get the default category (or create one if it doesn't exist)
            $defaultCategory = Category::where('business_id', $business_id)
                                    ->where('name', 'Default')
                                    ->where('category_type', 'product')
                                    ->first();

            if (!$defaultCategory) {
                // Create a default category if it doesn't exist
                $defaultCategory = Category::create([
                    'business_id' => $business_id,
                    'name' => 'Default',
                    'category_type' => 'product',
                    'created_by' => $user_id,
                    'parent_id' => 0,
                ]);
            }

            // Update the product's category to default
            $product->category_id = $defaultCategory->id;
            $product->save();

            // Log the activity
            activity()
                ->causedBy(auth()->user())
                ->performedOn($product)
                ->withProperties([
                    'product_name' => $product_name,
                    'product_id' => $product_id,
                    'action' => 'removed_from_none_added_category',
                    'old_category' => 'Not Added Products',
                    'new_category' => 'Default'
                ])
                ->log('product_removed_from_none_added_category');

            return response()->json([
                'success' => true,
                'message' => 'Product removed from None Added Products successfully',
                'product_id' => $product_id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error removing product from none added: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error removing product from None Added Products'
            ], 500);
        }
    }
} 