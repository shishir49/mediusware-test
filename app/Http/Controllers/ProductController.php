<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use\Illuminate\Support\Facades\Validator;
use DB;
use Image;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $priceRangeFrom = $request->get('price_from');
        $priceRangeTo   = $request->get('price_to');
        $variant        = $request->get('variant');
        $title          = $request->get('title');
        $date           = $request->get('date');

        // echo "<pre>";print_r(json_encode(Product::with('productPrice')->get()));die();
        
        $getList    = Product::query();

        if($priceRangeFrom && $priceRangeTo) {
            $getList->with('productPrice')
                        ->whereHas('productPrice',function ($q) use ($priceRangeFrom, $priceRangeTo) {
                            $q->whereBetween('price', [$priceRangeFrom, $priceRangeTo]);
                        });
        }

        if($title) {
            $getList->with('productPrice')
                        ->where('title', 'like' , '%'.$title.'%');
        }

        if($variant) {
            $getList->with('productPrice', 'productVariant')
                        ->whereHas('productVariant',function ($q) use ($variant) {
                            $q->where('variant', 'like' ,'%'.$variant.'%');
                        });            
        }

        if($date) {
            $getList->with('productPrice')
                        ->where('created_at', '>=' ,$date);
        }

        $productList      = $getList->paginate(10);
        $productVariants  = Variant::with('productVariants')->get();
        // echo "<pre>";print_r(json_encode($productList));die();
        
        return view('products.index', compact('productList', 'productVariants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // dd($request->all());

        $validation = Validator::make($request->all(), [
            'title'            => 'required',
            'sku'              => 'required|unique:products'
         ]);
  
         if($validation->fails()) {
            return response()->json(["errors" => $validation->errors()] ,400);
         } else {
          DB::Transaction(function() use($request) {
              $createProduct = Product::create([
                'title'        => $request->title,
                'sku'          => $request->sku,
                'description'  => $request->description
              ]);
    
              $path = '';
              $thumbnailPath = '';
  
              foreach($request->product_image as $key => $images) {
                  $photoFile = $request->product_image->file('product_image'); 
                  $path      = date('mdYHis').uniqid()."-".$photoFile->getClientOriginalName();
                  $photoFile->move(public_path('uploads'), $path);
      
                  $thumbnail = Image::make($photoFile->getRealPath());
                  $thumbnailPath = 'thumbnail'.date('mdYHis').uniqid()."-".$photoFile->getClientOriginalName();
                  $thumbnail->resize(150, 150, function ($constraint) {
                      $constraint->aspectRatio();
                  })->save($public_path('thumbnails').'/'.$thumbnailPath);
              
                  ProductImage::create([
                  'product_id'    => $createProduct->id,
                  'file_path'     => $path,
                  'thumbnail'     => $thumbnailPath
                  ]);
              }
                
              foreach($request->product_variant as $key => $variant) {
                  foreach($request->product_variant[$key]['tags'] as $subkey => $variants) {
                      ProductVariant::create([
                          'variant'       => $request->product_variant[$key]['tags'][$subkey],
                          'variant_id'    => $request->product_variant[$key]['option'],
                          'product_id'    => $createProduct->id
                      ]);
                  }    
              }
    
              foreach($request->product_variant_prices as $key => $variantPrice) {
                ProductVariantPrice::create([
                    'price'                   => $request->product_variant_prices[$key]['price'],
                    'stock'                   => $request->product_variant_prices[$key]['stock'],
                    'product_id'              => $createProduct->id
                ]);
              }
           });
  
           return response()->json(200);
         }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
