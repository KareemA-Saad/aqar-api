<?php

namespace Modules\HotelBooking\Http\Pagebuilder\Addons\Attractions;

use App\Facades\GlobalLanguage;
use App\Helpers\SanitizeInput;
use Modules\HotelBooking\Entities\Hotel;
use Plugins\PageBuilder\Fields\Image;
use Plugins\PageBuilder\Fields\Repeater;
use Plugins\PageBuilder\Fields\Text;
use Plugins\PageBuilder\Fields\Textarea;
use Plugins\PageBuilder\Helpers\RepeaterField;
use Plugins\PageBuilder\PageBuilderBase;
use Modules\HotelBooking\Entities\Room;

class AttractionArea extends PageBuilderBase
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


        //repeater
        $output .= Repeater::get([
            'settings' => $widget_saved_values,
            'multi_lang' => true,
            'id' => 'hotel_booking_attraction_area_repeater',
            'fields' => [

                [
                    'type' => RepeaterField::TEXT,
                    'name' => 'repeater_title',
                    'label' => __('Title')
                ],
                [
                    'type' => RepeaterField::TEXTAREA,
                    'name' => 'repeater_description',
                    'label' => __('Description')
                ],
                [
                    'type' => RepeaterField::IMAGE,
                    'name' => 'repeater_image',
                    'label' => __('Image')
                ]
            ]
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
        $top_title = $this->setting_item('top_title_'.$current_lang) ?? '';
        $repeater_data = $this->setting_item('hotel_booking_attraction_area_repeater') ?? '';

        $all_rooms = Room::where('is_featured',true)->orderBy("id","DESC")->with("room_types","room_types.country","room_types.state")->limit(10)->get();

        $data = [
            'padding_top'=> $padding_top,
            'padding_bottom'=> $padding_bottom,
            'top_title'=> $top_title,
            'repeater_data'=> $repeater_data,
            'all_rooms' =>$all_rooms
        ];

        return self::renderView('attractions.attraction-area', $data, 'HotelBooking');

    }

    public function addon_title()
    {
        return __('AttractionArea (Hotel Booking)');
    }
}
