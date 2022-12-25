@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="http://127.0.0.1:8000/product" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control title">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                       @foreach($productVariants as $variants)
                            <option selected disabled>Select an Option</option>
                            <optgroup label="{{ $variants->title }}">
                            @foreach($variants->productVariants as $variantList)
                                <option value="{{ $variantList->variant }}">{{ $variantList->variant }}</option>
                            @endforeach
                           </optgroup>
                       @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
        
        <div class="card-body">
            <div class="table-response">
                <table class="table" id="product_table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($productList as $key => $products)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $products->title }} <br> Created at : {{ $products->created_at }}</td>
                        <td>{{ $products->description }}</td>
                        <td>
                            <dl class="row mb-0 variant" style="height:100px;overflow:hidden" id="">
                            @foreach($products->productPrice as $prices)
                                <dt class="col-sm-3 pb-0">
                                @if($prices->productVariantOne != NULL)    
                                {{ $prices->productVariantOne->variant }}
                                @endif

                                @if($prices->productVariantTwo != NULL)    
                                / {{ $prices->productVariantTwo->variant }} 
                                @endif

                                @if($prices->productVariantThree != NULL)    
                                / {{ $prices->productVariantThree->variant }}
                                @endif
                                </dt>
                                <dd class="col-sm-9">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 pb-0">Price : {{ number_format($prices->price, 2) }}</dt>
                                        <dd class="col-sm-8 pb-0">InStock : {{ number_format($prices->stock,2) }}</dd>
                                    </dl>
                                </dd>
                            @endforeach
                            </dl>
                            <button id="" class="btn btn-sm btn-link getFull">Show more</button>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('product.edit', $products->id) }}" class="btn btn-success">Edit</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach

                    </tbody>

                </table>
            </div>
        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{($productList->currentpage()-1)*$productList->perpage()+1}} to {{$productList->currentpage()*$productList->perpage()}}
                    of  {{$productList->total()}} entries
                    </p>
                </div>
                <div class="col-md-2">
                  {!! $productList->links() !!}
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $(".getFull").click(function() {
                $(this).siblings('.variant').toggleClass('h-auto');
            })
            // $('#product_table').DataTable({
            //     ajax: 'http://127.0.0.1:8000/product-list',
            //     columns: [
            //         { data: 'title' },
            //         { data: 'description' },
            //         { data: 'variant' },
            //         { data: 'productPrice[]' },
            //         { data: 'hr.2' },
            //         { data: 'hr.1' },
            //     ],
            // });
            
            $(document).on('keyup', '.title', function(){
                event.preventDefault();
                $('#productList').append('<img style="position: fixed; left: 45%; top: 20%; z-index: 100000;" src="images/load.gif" />');
                var query = $(this).val();
                getProducts(query);
            });
            //-----------------------Get All Task Info ------------------------
            function getProducts(query = '')
            {
                $.ajax({
                url:"/product-list",
                method:'GET',
                data:{title:query},
                dataType:'json',
                success:function(data)
                {
                    console.log(data);
                    $('#productList').html(data);
                    $('#total_records').text(data.totalRow);
                }
                })
            }
            getProducts();
            
        })
    </script>

@endsection