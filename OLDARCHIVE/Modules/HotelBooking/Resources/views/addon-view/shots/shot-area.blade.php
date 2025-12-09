@php
    $lang_slug = get_user_lang();
    $all_rooms = $data['all_rooms'];
@endphp
<section class="attraction-area pat-50 pab-50">
    <div class="container">
        <div class="section-title center-text">
            <h2 class="title"> {{$data['top_title']}} </h2>
            <div class="section-title-line"> </div>
        </div>
        <div class="row g-4 mt-4">
            @foreach($all_rooms as $item)
            <div class="col-xl-4 col-sm-6 ">
                <div class="hotel-view theme-bg radius-20 card">
                    <a href="{{route('tenant.frontend.room_details',$item->slug)}}" class="hotel-view-thumb hotel-view-grid-thumb bg-image" {!! render_background_image_markup_by_attachment_id($item->room_image[0]->image_id) !!}>
                    </a>
                    <div class="hotel-view-contents">
                        <div class="hotel-view-contents-header">
                            @if($item->averageRating !=0 )
                            <span class="hotel-view-contents-review"> {{ round($item->averageRating,1) }} <span class="hotel-view-contents-review-count"> @if($item->reviews_count != 0) ({{$item->reviews_count}}) @endif </span> </span>
                            @endif
                            <h3 class="hotel-view-contents-title"> <a href="{{route('tenant.frontend.room_details',$item->slug)}}"> {{ $item->getTranslation('name',$lang_slug)}} </a> </h3>
                            <div class="hotel-view-contents-location mt-2">
                                <span class="hotel-view-contents-location-icon"> <i class="las la-map-marker-alt"></i> </span>
                                <span class="hotel-view-contents-location-para"> {{@$item->state->name}}, {{@$item->country->name}} </span>
                            </div>
                        </div>
                        <div class="hotel-view-contents-middle">
                            <div class="hotel-view-contents-flex">
                                @foreach($item->room_types->room_type_amenities as $data)
                                <div class="hotel-view-contents-icon myTooltip" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $data->getTranslation('name',$lang_slug)}}">
                                    <i class=" {{ $data->icon }}"></i>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="hotel-view-contents-bottom">
                            <div class="hotel-view-contents-bottom-flex">

                                <div class="hotel-view-contents-bottom-contents">
                                    <h4 class="hotel-view-contents-bottom-title" style="font-family: 'saudi_riyal'"> {!! @amount_with_currency_symbol($item->base_cost) !!}
                                        @if($item->duration)
                                        <sub>/ {{__($item->duration)}}</sub>
                                        @endif
                                    </h4>
                                </div>
                                <div class="btn-wrapper">
                                    <a href="{{route('tenant.frontend.room_details',$item->slug)}}" class="cmn-agency-btn cmn-agency-btn-bg-1 cmn-agency-btn-small rounded">{{__('Details')}} </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    </div>
</section>