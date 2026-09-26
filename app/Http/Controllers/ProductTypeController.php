<?php

namespace App\Http\Controllers;

use App\DataTables\ProductTypeDataTable;
use App\Models\Product;
use App\Models\ProductType;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class ProductTypeController extends AppBaseController
{
    public function index(ProductTypeDataTable $productTypeDataTable)
    {
        return $productTypeDataTable->render('product_types.index');
    }

    public function create()
    {
        return view('product_types.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), ProductType::$rules);
        if ($validator->fails()) {
            Flash::error($validator->errors()->first());
            return redirect()->back()->withInput();
        }

        $productType = ProductType::create($request->only(['name', 'status']));

        Flash::success($productType->name . ' saved successfully.');

        return redirect(route('productTypes.index'));
    }

    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $productType = ProductType::find($id);

        if (empty($productType)) {
            Flash::error('Product Type not found');
            return redirect(route('productTypes.index'));
        }

        return view('product_types.edit')->with('productType', $productType);
    }

    public function update($id, Request $request)
    {
        $id = Crypt::decrypt($id);
        $productType = ProductType::find($id);

        if (empty($productType)) {
            Flash::error('Product Type not found');
            return redirect(route('productTypes.index'));
        }

        $validator = Validator::make($request->all(), ProductType::$rules);
        if ($validator->fails()) {
            Flash::error($validator->errors()->first());
            return redirect()->back()->withInput();
        }

        $productType->update($request->only(['name', 'status']));

        Flash::success($productType->name . ' updated successfully.');

        return redirect(route('productTypes.index'));
    }

    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $productType = ProductType::find($id);

        if (empty($productType)) {
            Flash::error('Product Type not found');
            return redirect(route('productTypes.index'));
        }

        if (Product::where('type_id', $id)->exists()) {
            Flash::error('Unable to delete ' . $productType->name . ', it is being used by one or more products.');
            return redirect(route('productTypes.index'));
        }

        $productType->delete();

        Flash::success($productType->name . ' deleted successfully.');

        return redirect(route('productTypes.index'));
    }
}
