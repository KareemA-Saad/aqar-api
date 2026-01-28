<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run()
    {
        $testimonials = [
            [
                'name' => ['en_GB' => 'Sarah Johnson', 'ar' => 'سارة جونسون'],
                'designation' => ['en_GB' => 'Marketing Director', 'ar' => 'مديرة التسويق'],
                'company' => ['en_GB' => 'Tech Solutions Inc', 'ar' => 'شركة التقنية المتقدمة'],
                'description' => [
                    'en_GB' => 'This platform has revolutionized how we manage our online presence. The multi-tenant architecture and intuitive interface make it perfect for managing multiple client websites efficiently.',
                    'ar' => 'لقد أحدثت هذه المنصة ثورة في كيفية إدارة وجودنا على الإنترنت. تجعل البنية متعددة المستأجرين والواجهة البديهية من المثالي لإدارة مواقع العملاء المتعددة بكفاءة.'
                ],
                'status' => 1,
            ],
            [
                'name' => ['en_GB' => 'Ahmed Al-Rashid', 'ar' => 'أحمد الراشد'],
                'designation' => ['en_GB' => 'CEO', 'ar' => 'الرئيس التنفيذي'],
                'company' => ['en_GB' => 'Digital Innovations', 'ar' => 'الابتكارات الرقمية'],
                'description' => [
                    'en_GB' => 'Outstanding service and reliability. The real estate module perfectly fits our property management needs, and the multilingual support helps us serve our diverse clientele.',
                    'ar' => 'خدمة ممتازة وموثوقية عالية. وحدة العقارات تناسب تماما احتياجات إدارة الممتلكات لدينا، والدعم متعدد اللغات يساعدنا على خدمة عملائنا المتنوعين.'
                ],
                'status' => 1,
            ],
            [
                'name' => ['en_GB' => 'Emily Rodriguez', 'ar' => 'إيميلي رودريغيز'],
                'designation' => ['en_GB' => 'Project Manager', 'ar' => 'مديرة المشاريع'],
                'company' => ['en_GB' => 'Creative Agency Pro', 'ar' => 'وكالة الإبداع المحترفة'],
                'description' => [
                    'en_GB' => 'The flexibility and scalability of this platform are unmatched. We successfully launched over 50 client websites using various themes and modules.',
                    'ar' => 'المرونة وقابلية التوسع في هذه المنصة لا مثيل لها. لقد نجحنا في إطلاق أكثر من 50 موقعًا للعملاء باستخدام مواضيع ووحدات مختلفة بأداء سلس.'
                ],
                'status' => 1,
            ],
            [
                'name' => ['en_GB' => 'Michael Chen', 'ar' => 'مايكل تشين'],
                'designation' => ['en_GB' => 'Technical Lead', 'ar' => 'القائد التقني'],
                'company' => ['en_GB' => 'StartupHub', 'ar' => 'مركز الشركات الناشئة'],
                'description' => [
                    'en_GB' => 'Exceptional platform architecture and developer-friendly APIs. The pricing plans are flexible, and the support team is incredibly responsive.',
                    'ar' => 'هندسة منصة استثنائية وواجهات برمجة تطبيقات ودودة للمطورين. خطط التسعير مرنة، وفريق الدعم سريع الاستجابة بشكل لا يصدق.'
                ],
                'status' => 1,
            ],
            [
                'name' => ['en_GB' => 'Fatima Al-Zahra', 'ar' => 'فاطمة الزهراء'],
                'designation' => ['en_GB' => 'Business Development Manager', 'ar' => 'مديرة تطوير الأعمال'],
                'company' => ['en_GB' => 'Gulf Enterprises', 'ar' => 'مؤسسات الخليج'],
                'description' => [
                    'en_GB' => 'This SaaS solution has streamlined our business operations significantly. The multi-language support makes it perfect for Middle East markets.',
                    'ar' => 'لقد بسط هذا الحل السحابي عمليات أعمالنا بشكل كبير. الدعم متعدد اللغات وخيارات التخصيص الإقليمية تجعله مثاليًا لأسواق الشرق الأوسط.'
                ],
                'status' => 1,
            ]
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create([
                'name' => $testimonial['name'],
                'designation' => $testimonial['designation'],
                'company' => $testimonial['company'],
                'description' => $testimonial['description'],
                'image' => null, // Will be populated with actual images later
                'status' => $testimonial['status'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}