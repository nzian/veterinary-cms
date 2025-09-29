<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Branch;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('branch')->get();
        $branches = Branch::all();

        return view('product', compact('products', 'branches'));
    }
//sanitize
  public function store(Request $request)
{
    $validated = $request->validate([
        'prod_name' => 'required|string|max:255',
        'prod_category' => 'nullable|string|max:255',
        'prod_description' => 'required|string|max:1000',
        'prod_price' => 'required|numeric|min:0',
        'prod_stocks' => 'required|integer|min:0',
        'prod_reorderlevel' => 'nullable|integer|min:0',
        'branch_id' => 'required|exists:tbl_branch,branch_id',
        'prod_image' => 'nullable|image|max:2048', // ✅ allow image upload
    ]);

    if ($request->hasFile('prod_image')) {
        $validated['prod_image'] = $request->file('prod_image')->store('products', 'public');
    }

    Product::create($validated);

    return redirect()->back()->with('success', 'Product added successfully!');
}

public function update(Request $request, $id)
{
    $validated = $request->validate([
        'prod_name' => 'required|string|max:255',
        'prod_category' => 'nullable|string|max:255',
        'prod_description' => 'required|string|max:1000',
        'prod_price' => 'required|numeric|min:0',
        'prod_stocks' => 'required|integer|min:0',
        'prod_reorderlevel' => 'nullable|integer|min:0',
        'branch_id' => 'required|exists:tbl_branch,branch_id',
        'prod_image' => 'nullable|image|max:2048',
    ]);

    $product = Product::findOrFail($id);

    if ($request->hasFile('prod_image')) {
        $validated['prod_image'] = $request->file('prod_image')->store('products', 'public');
    }

    $product->update($validated);

    return redirect()->back()->with('success', 'Product updated successfully!');
}

public function updateInventory(Request $request)
{
    $validated = $request->validate([
        'prod_id' => 'required|exists:tbl_prod,prod_id',
        'prod_damaged' => 'nullable|integer|min:0',
        'prod_pullout' => 'nullable|integer|min:0',
        'prod_expiry' => 'nullable|date',
    ]);

    $product = Product::findOrFail($validated['prod_id']);
    $product->update($validated);

    return redirect()->back()->with('success', 'Inventory updated successfully!');
}


    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return redirect()->back()->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting product: ' . $e->getMessage());
        }
    }
}