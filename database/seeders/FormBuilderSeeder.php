<?php

namespace Database\Seeders;

use App\Models\FormBuilder;
use Illuminate\Database\Seeder;

class FormBuilderSeeder extends Seeder
{
    public function run()
    {
        $forms = [
            [
                'title' => 'Contact Us',
                'email' => 'info@yoursite.com',
                'button_text' => 'Send Message',
                'success_message' => 'Thank you for your message! We will get back to you soon.',
                'fields' => json_encode([
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Full Name',
                        'placeholder' => 'Enter your full name',
                        'required' => true,
                        'validation' => 'required|string|max:255'
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'Enter your email address',
                        'required' => true,
                        'validation' => 'required|email|max:255'
                    ],
                    [
                        'type' => 'text',
                        'name' => 'subject',
                        'label' => 'Subject',
                        'placeholder' => 'Enter message subject',
                        'required' => true,
                        'validation' => 'required|string|max:255'
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'message',
                        'label' => 'Message',
                        'placeholder' => 'Enter your message here...',
                        'required' => true,
                        'validation' => 'required|string|min:10'
                    ]
                ])
            ],
            [
                'title' => 'Property Inquiry',
                'email' => 'properties@yoursite.com',
                'button_text' => 'Submit Inquiry',
                'success_message' => 'Your property inquiry has been submitted successfully. Our team will contact you shortly.',
                'fields' => json_encode([
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => 'Full Name',
                        'placeholder' => 'Enter your full name',
                        'required' => true,
                        'validation' => 'required|string|max:255'
                    ],
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'Enter your email address',
                        'required' => true,
                        'validation' => 'required|email|max:255'
                    ],
                    [
                        'type' => 'tel',
                        'name' => 'phone',
                        'label' => 'Phone Number',
                        'placeholder' => 'Enter your phone number',
                        'required' => true,
                        'validation' => 'required|string|max:20'
                    ],
                    [
                        'type' => 'select',
                        'name' => 'property_type',
                        'label' => 'Property Type',
                        'required' => true,
                        'options' => ['Apartment', 'Villa', 'Office', 'Commercial', 'Other'],
                        'validation' => 'required|string'
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'message',
                        'label' => 'Additional Details',
                        'placeholder' => 'Tell us more about your requirements...',
                        'required' => false,
                        'validation' => 'nullable|string'
                    ]
                ])
            ],
            [
                'title' => 'Newsletter Subscription',
                'email' => 'newsletter@yoursite.com',
                'button_text' => 'Subscribe',
                'success_message' => 'Thank you for subscribing to our newsletter!',
                'fields' => json_encode([
                    [
                        'type' => 'email',
                        'name' => 'email',
                        'label' => 'Email Address',
                        'placeholder' => 'Enter your email address',
                        'required' => true,
                        'validation' => 'required|email|max:255'
                    ],
                    [
                        'type' => 'text',
                        'name' => 'first_name',
                        'label' => 'First Name',
                        'placeholder' => 'Enter your first name',
                        'required' => false,
                        'validation' => 'nullable|string|max:255'
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'interests',
                        'label' => 'Interests',
                        'required' => false,
                        'options' => ['Real Estate Updates', 'Market News', 'Investment Tips', 'New Properties'],
                        'validation' => 'nullable|array'
                    ]
                ])
            ]
        ];

        foreach ($forms as $form) {
            FormBuilder::create([
                'title' => $form['title'],
                'email' => $form['email'],
                'button_text' => $form['button_text'],
                'success_message' => $form['success_message'],
                'fields' => $form['fields'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}