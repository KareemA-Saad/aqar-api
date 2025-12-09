<section class="guest-area pat-50 pab-50">
    <div class="container">
        <div class="section-title center-text">
            <h2 class="title"> {{$data['top_title']}} </h2>
            <div class="section-title-shapes"> </div>
        </div>
        <div class="custom-tab guest-wrapper mt-5">
            <div class="custom-tab-menu">
                <ul class="tab-menu guest-wrapper-images list-style-none">
                    @foreach($data['testimonials'] as $item)
                        <li class="guest-wrapper-images-single {{ $loop->first ? 'active' : '' }}">
                            <img src="{!! render_image_url_by_attachment_id($item->image) !!}" alt="">
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="row justify-content-center">
                <div class="col-xxl-5 col-lg-6 col-md-8">
                    <div class="tab-area">

                        @foreach($data['testimonials'] as $item)
                        <div class="single-guest tab-item  center-text {{ $loop->first ? 'active' : '' }}">
                            <div class="single-guest-thumb">
                                <img src="{!! render_image_url_by_attachment_id($item->image) !!}" alt="">
                            </div>
                            <div class="single-guest-contents mt-4">
                                <p class="single-guest-contents-para"> {{$item->description}}</p>
                                <h4 class="single-guest-contents-title mt-3"> {{$item->name}} </h4>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
