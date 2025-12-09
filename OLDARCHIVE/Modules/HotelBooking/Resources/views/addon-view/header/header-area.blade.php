<style>
    .header-top .header-info-left .listing .listItem {
        color: black;
    }
    .btn-check:active+.btn-outline-secondary, .btn-check:checked+.btn-outline-secondary, .btn-outline-secondary.active, .btn-outline-secondary.dropdown-toggle.show, .btn-outline-secondary:active {
            color: #fff;
            background-color: var(--main-color-one);
            border-color: #6c757d;
        }
        .btn-outline-secondary{
           color: var(--paragraph-color);
        }
        .btn-outline-secondary:hover{
            color: var(--main-color-one);
            background-color: unset;
        }
        .banner-right-bg {
            position: absolute  !important;
            top: 0;
            left: 0;
            right: 0;
            z-index: 0;
            width: 100% !important;
        }
</style>
<!-- Banner area Starts -->
<div class="banner-area banner-area-one">
    <div class="container-fluid p-0">
        <div class="row align-items-center flex-column-reverse flex-lg-row">
            <div class="col-lg-6">
                <div class="banner-single banner-single-one percent-padding">
                    <div class="banner-single-content">
                        <h2 class="banner-single-content-title fw-700 white-color"> {{$data['title']}} </h2>
                        <p class="banner-single-content-para mt-3 white-color"> {{$data['description']}} </p>
                        <div class="banner-location banner-location-one theme-bg radius-5 mt-5">
                            <form action="{{route('tenant.frontend.search_room')}}" method="post">
                                @csrf
                                <ul class="nav nav-tabs" style="margin-bottom: revert;border-bottom: unset;">
                                    <li>
                                        <input type="radio" class="btn-check" name="property_type" id="sell" autocomplete="off" checked value="sell">
                                        <label class="btn btn-outline-secondary" for="sell" style="border: unset;">{{__('Sell')}}</label>
                                    </li>
                                    <li>
                                        <input type="radio" class="btn-check" name="property_type" id="rent" autocomplete="off" value="rent">
                                        <label class="btn btn-outline-secondary" for="rent" style="border: unset;">{{__('Rent')}}</label>
                                    </li>
                                    <li>
                                        <input type="radio" class="btn-check" name="property_type" id="project" autocomplete="off" value="project">
                                        <label class="btn btn-outline-secondary" for="project" style="border: unset;">{{__('Projects')}}</label>
                                    </li>
                                </ul>
                                <div class="banner-location-flex">
                                    <div class="banner-location-single">
                                        <div class="banner-location-single-flex">
                                            <div class="banner-location-single-icon">
                                                <i class="las la-search"></i>
                                            </div>
                                            <div class="banner-location-single-contents">
                                                <span class="banner-location-single-contents-subtitle"> {{__('Keyword')}} </span>
                                                <div class="banner-location-single-contents-dropdown custom-select">
                                                    <input type="text" name="keyword" id="keyword" class="form-control" style="height: unset;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="banner-location-single">
                                        <div class="banner-location-single-flex">
                                            <div class="banner-location-single-icon">
                                                <i class="las la-map-marker"></i>
                                            </div>
                                            <div class="banner-location-single-contents">
                                                <span class="banner-location-single-contents-subtitle"> {{__('State')}} </span>
                                                <div class="banner-location-single-contents-dropdown custom-select">
                                                    <select name="state" id="state" class="form-control">
                                                        <option value="">{{__('Select state')}}</option>
                                                        @foreach (\Modules\CountryManage\Entities\State::get() as $item)
                                                        <option value="{{$item->id}}">{{$item->name}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="banner-location-single-search">
                                        <div class="search-suggestions-wrapper">
                                            <button type="submit" class="search-click-icon"><i class="las la-search"></i></button>
                                        </div>
                                        <div class="search-suggestion-overlay"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 bg-image banner-right-bg radius-20" {!! render_background_image_markup_by_attachment_id($data['background_image']) !!}"></div>
        </div>
    </div>
</div>
<!-- Banner area end -->
