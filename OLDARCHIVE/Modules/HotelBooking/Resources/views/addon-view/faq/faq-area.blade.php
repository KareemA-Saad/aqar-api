@php
    $lang_slug = get_user_lang() ?? \App\Facades\GlobalLanguage::default_slug();
@endphp
<section class="question-area pat-50 pab-50">
    <div class="container">
        <div class="section-title center-text">
            <h2 class="title"> {{$data['top_title']}} </h2>
            <div class="section-title-shapes"> </div>
        </div>
        <div class="row g-4 mt-4">
            <div class="col-xl-8 col-lg-7">
                <div class="faq-wrapper">
                    <div class="faq-contents">
                        @foreach($data['faqs'] as $item)
                            <div class="faq-item wow fadeInLeft" data-wow-delay=".1s">
                                <h3 class="faq-title">
                                    {{ $item->getTranslation('title',$lang_slug)}}
                                </h3>
                                <div class="faq-panel">
                                    <p class="faq-para">
                                     {{ $item->getTranslation('description',$lang_slug)}}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-lg-5">
                <div class="faq-question faq-question-border radius-10 sticky-top">
                    <h3 class="faq-question-title"> Still got questions? </h3>
                    <div class="faq-question-form custom-form mat-20">
                        @if(!empty($data['custom_form_id_faq']))
                            @php
                                $form_details = \App\Models\FormBuilder::find($data['custom_form_id_faq']);
                            @endphp
                        @endif
                        {!! \App\Helpers\FormBuilderCustom::render_form(optional(@$form_details)->id,null,null,'btn-default') !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
