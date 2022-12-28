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
        
        return view("products.index", compact('productList', 'productVariants'));
    }
    
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }
    
    public function store(Request $request)
    {
        dd($request->all());
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
                        $getId = ProductVariant::create([
                            'variant'       => $request->product_variant[$key]['tags'][$subkey],
                            'variant_id'    => $request->product_variant[$key]['option'],
                            'product_id'    => $createProduct->id
                        ]);
                    }  
              }

              $getVariants = ProductVariant::where('product_id', $createProduct->id)->get()->toArray();
              
              foreach($getVariants as $arrayOfVariants) {
                $product_variant_count[$arrayOfVariants['id']] = $arrayOfVariants['variant'];
              }
    
              foreach($request->product_variant_prices as $masterkey => $variantPrice) {
                $makeArray = explode("/", $variantPrice['title']);

                foreach($makeArray as $k => $ding) {
                    if($makeArray[$k] != "") {
                        foreach($product_variant_count as $dd => $dong) {
                            $variant_one = ProductVariant::where('product_id', $createProduct->id)->where('variant', $makeArray[0])->first();
                            $variant_two = ProductVariant::where('product_id', $createProduct->id)->where('variant', $makeArray[1])->first();
                            $variant_three = ProductVariant::where('product_id', $createProduct->id)->where('variant', $makeArray[2])->first();
                        }
                    } 
                }
                
                ProductVariantPrice::create([
                    'price'                   => $request->product_variant_prices[$masterkey]['price'],
                    'stock'                   => $request->product_variant_prices[$masterkey]['stock'],
                    'product_id'              => $createProduct->id,
                    'product_variant_one'     => $variant_one['id'],
                    'product_variant_two'     => $variant_two['id'],
                    'product_variant_three'   => $variant_three['id'],
                ]);
              }
           });
  
           return response()->json(200);
         }
    }
    
    public function show($product)
    {

    }
    
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $product = Product::with('productPrice', 'productVariant')->where('id', $product->id)->first();
        return view('products.edit', compact('variants', 'product'));
    }
    
    public function update(Request $request, Product $product)
    {
        //
    }
    
    public function destroy(Product $product)
    {
        //
    }
}
