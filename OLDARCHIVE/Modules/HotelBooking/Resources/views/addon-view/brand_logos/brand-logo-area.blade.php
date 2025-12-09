
<div class="brand-area pat-50 pab-50">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="global-slick-init attraction-slider slider-inner-margin" data-slidesToShow="6" data-infinite="true" data-arrows="false" data-dots="false" data-swipeToSlide="true" data-autoplay="true" data-autoplaySpeed="2500" data-prevArrow='<div class="prev-icon"><i class="las la-angle-left"></i></div>'
                     data-nextArrow='<div class="next-icon"><i class="las la-angle-right"></i></div>' data-responsive='[{"breakpoint": 1400,"settings": {"slidesToShow": 5}},{"breakpoint": 1200,"settings": {"slidesToShow": 4}},{"breakpoint": 992,"settings": {"slidesToShow": 3}},{"breakpoint": 576, "settings": {"slidesToShow": 2} }]'>
                    @foreach($data['brands'] as $item)
                        <div class="single-brand">
                            <a href="javascript:void(0)" class="single-brand-thumb">
                               {!! render_image_markup_by_attachment_id($item['image']) !!}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
