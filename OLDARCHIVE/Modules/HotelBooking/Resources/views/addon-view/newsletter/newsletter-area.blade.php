<div class="newsletter-area pat-50">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="newsletter-wrapper newsletter-bg radius-20 newsletter-wrapper-padding wow zoomIn" data-wow-delay=".3s">
                    <div class="newsletter-wrapper-shapes">
                        {!! render_image_markup_by_attachment_id($data['shape_one']) !!}
                        {!! render_image_markup_by_attachment_id($data['shape_two']) !!}
                    </div>
                    <div class="newsletter-contents center-text">
                        <h3 class="newsletter-contents-title"> {{$data['title']}} </h3>
                        <p class="newsletter-contents-para mt-3"> {{$data['description']}} </p>
                        <div class="newsletter-contents-form custom-form mt-4">
                            <x-error-msg/>
                            <x-flash-msg/>
                            <form action="#"  class="wow ladeInUp"  data-wow-delay="0.2s">
                                <input type="hidden" name="_token" value="{{csrf_token()}}">
                                <div class="single-input">
                                    <input type="email" name="email"  class="form--control email" placeholder="{{__($data['button_placeholder_text'])}}">
                                    <button type="submit" class=" subscribe-btn newsletter-submit-btn"> {{__($data['button_text'])}} </button>
                                </div>
                                <div class="form-message-show mt-2"></div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
