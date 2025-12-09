<section class="blog-area pat-50 pab-50">
    @php
        $user_lang = get_user_lang();
        $lang_slug = $user_lang ?? \App\Facades\GlobalLanguage::default_slug();
    @endphp
    <div class="container">
        <div class="section-title center-text">
            <h2 class="title"> {{$data['title']}} </h2>

            <div class="section-title-shapes"> </div>
        </div>
        <div class="row gy-4 mt-4">
            @foreach($data['blogs'] ?? [] as $item)
                @php
                    $url = route('tenant.frontend.blog.single',$item->slug);
                    $category = $item->category?->title;
                    $category_route = route('tenant.frontend.blog.category',['id'=> $item->category_id, 'any' => \Illuminate\Support\Str::slug($category)]);
                @endphp
            <div class="col-xxl-4 col-lg-4 col-md-6">
                <div class="single-blog">
                    <div class="single-blog-thumbs">
                        <a href="{{$url}}"> {!! render_image_markup_by_attachment_id($item->image) !!} </a>
                    </div>
                    <div class="single-blog-contents mt-3">
                        <div class="single-blog-contents-tags mt-3">
                                <span class="single-blog-contents-tags-item">
                                    <a href="javascript:void(0)"> <i class="las la-tag"></i>  {{date('d M Y',strtotime($item->created_at))}}     </a>
                                </span>
                            <span class="single-blog-contents-tags-item"> <a href="javascript:void(0)"> {{ $data['blog_category']->title }}</a> </span>
                        </div>
                        <h3 class="wedding_blog__contents__title mt-3">
                            <a href="{{$url}}">{{ $item->getTranslation('title',$lang_slug)}}</a>
                        </h3>
                       <h4 class="single-blog-contents-title mt-3"> <a href="{{$url}}">   {{ str(strip_tags($item->getTranslation('blog_content',$lang_slug)))->limit(80) }}</a> </h4>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
