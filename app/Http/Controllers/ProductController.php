<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\AmazonProductScrapper;
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

//        return view('product.index');
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function show(Request $request)
    {
        $id = $request->get('id');

        if (empty($id)) {
            throw new \Exception('Product id is required!');
        }

        $product = Product::where('asin',$id)->first();
        if (empty($product['dispatches_from'])){
            $product = (new AmazonProductScrapper())->loadProductAsin($id);
        }

        return $product;
//        return view('product.single',['data'=> $product]);


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product $product
     * @return void
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\Product $product
     * @return void
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
