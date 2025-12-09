<?php

namespace Modules\HotelBooking\Http\Pagebuilder\Addons\BrandLogos;

use App\Facades\GlobalLanguage;
use App\Helpers\SanitizeInput;
use App\Models\Brand;
use Plugins\PageBuilder\PageBuilderBase;

class BrandLogoArea extends PageBuilderBase
{
    public function preview_image()
    {
        return 'brand-logo-area.png';
    }

    public function setAssetsFilePath()
    {
        return externalAddonImagepath('HotelBooking');
    }

    public function admin_render()
    {
        $output = $this->admin_form_before();
        $output .= $this->admin_form_start();
        $output .= $this->default_fields();

        $widget_saved_values = $this->get_settings();
        $output .= $this->admin_language_tab(); //have to start language tab from here on
        $output .= $this->admin_language_tab_start();
        $all_languages = GlobalLanguage::all_languages();

        foreach ($all_languages as $key => $lang) {
            $output .= $this->admin_language_tab_content_start([
                'class' => $key == 0 ? 'tab-pane fade show active' : 'tab-pane fade',
                'id' => "nav-home-" . $lang->slug
            ]);
            $output .= $this->admin_language_tab_content_end();
        }
        $output .= $this->admin_language_tab_end(); //have to end language tab



        // add padding option
        $output .= $this->padding_fields($widget_saved_values);
        $output .= $this->admin_form_submit_button();
        $output .= $this->admin_form_end();
        $output .= $this->admin_form_after();

        return $output;
    }

    public function frontend_render()
    {
        $current_lang = get_user_lang();

        $padding_top = SanitizeInput::esc_html($this->setting_item('padding_top'));
        $padding_bottom = SanitizeInput::esc_html($this->setting_item('padding_bottom'));

        $brands = Brand::where('status',1)->select(['id','status','image'])->orderBy('id','desc')->get();


        $data = [
            'padding_top'=> $padding_top,
            'padding_bottom'=> $padding_bottom,
            'brands' => $brands
        ];

        return self::renderView('brand_logos.brand-logo-area', $data, 'HotelBooking');
    }

    public function addon_title()
    {
        return __('BrandLogoArea (Hotel Booking)');
    }
}
