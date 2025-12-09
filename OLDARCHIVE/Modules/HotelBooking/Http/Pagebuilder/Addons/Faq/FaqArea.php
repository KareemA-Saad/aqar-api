<?php

namespace Modules\HotelBooking\Http\Pagebuilder\Addons\Faq;

use App\Facades\GlobalLanguage;
use App\Helpers\SanitizeInput;
use App\Models\FormBuilder;
use Modules\HotelBooking\Entities\Faq;
use Plugins\PageBuilder\Fields\Image;
use Plugins\PageBuilder\Fields\Repeater;
use Plugins\PageBuilder\Fields\Select;
use Plugins\PageBuilder\Fields\Text;
use Plugins\PageBuilder\Fields\Textarea;
use Plugins\PageBuilder\Helpers\RepeaterField;
use Plugins\PageBuilder\PageBuilderBase;

class FaqArea extends PageBuilderBase
{
    public function preview_image()
    {
        return 'header-area.png';
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

            $output .= Text::get([
                'name' => 'top_title_'.$lang->slug,
                'label' => __('Top Title'),
                'value' => $widget_saved_values['top_title_'.$lang->slug] ?? null,
            ]);

            $output .= $this->admin_language_tab_content_end();
        }
        $output .= $this->admin_language_tab_end(); //have to end language tab

        $form = FormBuilder::all()->pluck('title','id')->toArray();
        $output .= Select::get([
            'name' => 'custom_form_id_faq',
            'label' => __('Custom Form'),
            'placeholder' => __('Select form'),
            'options' => $form,
            'value' =>   $widget_saved_values['custom_form_id_faq'] ?? []
        ]);

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
        $custom_form_id_faq = $this->setting_item('custom_form_id_faq');
        $top_title = $this->setting_item('top_title_'.$current_lang) ?? '';

        $faqs = Faq::get();

        $data = [
            'padding_top'=> $padding_top,
            'padding_bottom'=> $padding_bottom,
            'top_title'=> $top_title,
            'faqs'=> $faqs,
            'custom_form_id_faq'=> $custom_form_id_faq,
        ];
        return self::renderView('faq.faq-area', $data, 'HotelBooking');
    }

    public function addon_title()
    {
        return __('FaqArea (Hotel Booking)');
    }
}
