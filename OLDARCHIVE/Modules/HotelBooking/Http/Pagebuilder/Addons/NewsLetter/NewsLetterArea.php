<?php

namespace Modules\HotelBooking\Http\Pagebuilder\Addons\NewsLetter;

use App\Facades\GlobalLanguage;
use App\Helpers\SanitizeInput;
use Plugins\PageBuilder\Fields\Image;
use Plugins\PageBuilder\Fields\Text;
use Plugins\PageBuilder\Fields\Textarea;
use Plugins\PageBuilder\PageBuilderBase;

class NewsLetterArea extends PageBuilderBase
{
    public function preview_image()
    {
        return 'newsletter-area.png';
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
                'name' => 'title_'.$lang->slug,
                'label' => __('Title'),
                'value' => $widget_saved_values['title_'.$lang->slug] ?? null,
            ]);

            $output .= Textarea::get([
                'name' => 'description_'.$lang->slug,
                'label' => __('Description'),
                'value' => $widget_saved_values['description_'.$lang->slug] ?? null,
            ]);



            $output .= Text::get([
                'name' => 'button_text_'.$lang->slug,
                'label' => __('Button Text'),
                'value' => $widget_saved_values['title_'.$lang->slug] ?? null,
            ]);
            $output .= Text::get([
                'name' => 'button_placeholder_text_'.$lang->slug,
                'label' => __('Input Placeholder Text'),
                'value' => $widget_saved_values['button_placeholder_text_'.$lang->slug] ?? null,
            ]);

            $output .= $this->admin_language_tab_content_end();
        }

        $output .= Image::get([
            'name' => 'shape_one',
            'label' => __('Shape One'),
            'value' => $widget_saved_values['shape_one'] ?? null,
        ]);

        $output .= Image::get([
            'name' => 'shape_two',
            'label' => __('Shape Two'),
            'value' => $widget_saved_values['shape_two'] ?? null,
        ]);

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
        $title = $this->setting_item('title_'.$current_lang) ?? '';
        $description = $this->setting_item('description_'.$current_lang) ?? '';
        $shape_one = $this->setting_item('shape_one') ?? '';
        $shape_two = $this->setting_item('shape_two') ?? '';

        $button_text = $this->setting_item('button_text_'.$current_lang) ?? '';
        $button_placeholder_text = $this->setting_item('button_placeholder_text_'.$current_lang) ?? '';

        $data = [
            'padding_top'=> $padding_top,
            'padding_bottom'=> $padding_bottom,
            'title'=> $title,
            'description'=> $description,
            'shape_one'=> $shape_one,
            'shape_two'=> $shape_two,
            'button_text'=> $button_text,
            'button_placeholder_text'=> $button_placeholder_text,
        ];

        return self::renderView('newsletter.newsletter-area', $data, 'HotelBooking');
    }

    public function addon_title()
    {
        return __('NewsLetterArea (Hotel Booking)');
    }
}
