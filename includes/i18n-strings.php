<?php
/**
 * Static UI string dictionary for t(). Keyed by dot-namespaced string;
 * each entry has 'th' (required) and 'en' (falls back to 'th' if missing).
 * Admin-entered content (pages, teacher profiles, etc.) is translated
 * separately via lang_field(), not here.
 */
return [
    // Navigation
    'nav.home' => ['th' => 'หน้าหลัก', 'en' => 'Home'],
    'nav.curriculum' => ['th' => 'เกี่ยวกับหลักสูตร', 'en' => 'About the Program'],
    'nav.course' => ['th' => 'หลักสูตรปริญญาตรี', 'en' => "Bachelor's Program"],
    'nav.history' => ['th' => 'ประวัติหลักสูตร', 'en' => 'Program History'],
    'nav.history_short' => ['th' => 'ประวัติ', 'en' => 'History'],
    'nav.subjects' => ['th' => 'รายวิชาที่เปิดสอน', 'en' => 'Courses Offered'],
    'nav.teachers' => ['th' => 'อาจารย์', 'en' => 'Faculty'],
    'nav.classroom' => ['th' => 'ห้องเรียน', 'en' => 'Classroom'],
    'nav.student_db' => ['th' => 'ฐานข้อมูลนักศึกษา', 'en' => 'Student Database'],
    'nav.students' => ['th' => 'นักศึกษาปัจจุบัน', 'en' => 'Current Students'],
    'nav.alumni' => ['th' => 'ศิษย์เก่า', 'en' => 'Alumni'],
    'nav.student_services' => ['th' => 'บริการนักศึกษา', 'en' => 'Student Services'],
    'nav.student_services_info' => ['th' => 'ข้อมูลทั่วไป', 'en' => 'General Information'],
    'nav.student_works' => ['th' => 'ผลงานนักศึกษา', 'en' => 'Student Works'],
    'nav.contact' => ['th' => 'ติดต่อเรา', 'en' => 'Contact Us'],
    'nav.schedule' => ['th' => 'ตารางเวลา', 'en' => 'Schedule'],
    'nav.academic_calendar' => ['th' => 'ปฏิทินการศึกษา', 'en' => 'Academic Calendar'],
    'nav.activity_calendar' => ['th' => 'ปฏิทินกิจกรรม', 'en' => 'Activity Calendar'],
    'nav.for_teachers' => ['th' => 'สำหรับอาจารย์', 'en' => 'For Faculty'],

    // Topbar
    'topbar.login' => ['th' => 'เข้าสู่ระบบ', 'en' => 'Log In'],
    'topbar.dashboard' => ['th' => 'หลังบ้าน', 'en' => 'Dashboard'],
    'topbar.enroll' => ['th' => 'สมัครเรียน', 'en' => 'Apply Now'],
    'topbar.switch_to_en' => ['th' => 'EN', 'en' => 'EN'],
    'topbar.switch_to_th' => ['th' => 'ไทย', 'en' => 'ไทย'],
    'topbar.mourning_on' => ['th' => 'โหมดไว้อาลัย', 'en' => 'Mourning mode'],
    'topbar.mourning_off' => ['th' => 'สีปกติ', 'en' => 'Normal colors'],
    'topbar.menu' => ['th' => 'เปิดเมนู', 'en' => 'Open menu'],

    // Homepage
    'home.hero_works' => ['th' => 'ดูผลงานนักศึกษา', 'en' => 'Student Works'],
    'home.posts_heading' => ['th' => 'ข่าวประชาสัมพันธ์ & กิจกรรม', 'en' => 'News & Activities'],
    'home.read_more' => ['th' => 'อ่านเพิ่มเติม', 'en' => 'Read more'],
    'home.gallery_heading' => ['th' => 'ภาพกิจกรรม', 'en' => 'Activity Gallery'],
    'home.facebook_heading' => ['th' => 'ติดตามข่าวสารและกิจกรรมทาง Facebook', 'en' => 'Follow Us on Facebook'],
    'home.media_heading' => ['th' => 'ติดตามข่าวสารและกิจกรรม', 'en' => 'News & Activities'],
    'home.media_video_label' => ['th' => 'วิดีโอแนะนำสาขา', 'en' => 'Program video'],
    'home.media_facebook_label' => ['th' => 'อัปเดตล่าสุดจาก Facebook', 'en' => 'Latest from Facebook'],
    'home.quick_course' => ['th' => 'หลักสูตร', 'en' => 'Curriculum'],
    'home.quick_course_sub' => ['th' => 'เรียนอะไรบ้าง', 'en' => 'What you will learn'],
    'home.quick_works' => ['th' => 'ผลงานนักศึกษา', 'en' => 'Student works'],
    'home.quick_works_sub' => ['th' => 'Animation · Game · Film', 'en' => 'Animation · Game · Film'],
    'home.quick_contact' => ['th' => 'ติดต่อเรา', 'en' => 'Contact'],
    'home.quick_contact_sub' => ['th' => 'สอบถาม / สมัครเรียน', 'en' => 'Questions & admission'],
    'home.facebook_followers' => ['th' => 'ผู้ติดตาม', 'en' => 'followers'],
    'home.facebook_follow' => ['th' => 'ติดตามเพจ', 'en' => 'Follow Page'],
    'home.facebook_message' => ['th' => 'ส่งข้อความ', 'en' => 'Message'],
    'home.facebook_link' => ['th' => 'ไปที่เพจ Facebook', 'en' => 'Visit Facebook Page'],
    'home.fields_heading' => ['th' => 'รายวิชาที่เปิดสอน', 'en' => 'Courses Offered'],
    'home.stats_heading' => ['th' => 'สถิติการรับนักศึกษา', 'en' => 'Admission Statistics'],
    'home.stats_year_prefix' => ['th' => 'ปี', 'en' => 'Year'],
    'home.stats_empty' => ['th' => 'ยังไม่มีข้อมูลสถิติ', 'en' => 'No statistics yet'],
    'home.stats_latest' => ['th' => 'รับนักศึกษาล่าสุด', 'en' => 'Latest intake'],
    'home.stats_change' => ['th' => 'เปลี่ยนแปลงจากปีก่อน', 'en' => 'Change vs previous year'],
    'home.stats_average' => ['th' => 'เฉลี่ยต่อปี', 'en' => 'Average per year'],
    'home.stats_total_all' => ['th' => 'รวมทุกปี', 'en' => 'Total'],
    'home.stats_years' => ['th' => 'ปี', 'en' => 'years'],
    'home.student_stats_heading' => ['th' => 'สถิตินักศึกษา ชั้นปีที่ 1-4', 'en' => 'Student Statistics by Year Level 1-4'],
    'home.student_stats_year' => ['th' => 'ชั้นปีที่', 'en' => 'Year'],
    'home.student_stats_academic_year' => ['th' => 'ปีการศึกษา', 'en' => 'Academic year'],
    'home.student_stats_people' => ['th' => 'คน', 'en' => 'students'],
    'home.student_stats_total' => ['th' => 'รวมทั้งหมด', 'en' => 'Total'],

    // Calendar pages
    'calendar.no_links' => ['th' => 'ยังไม่มีลิงก์', 'en' => 'No links yet'],
    'student_works.no_works' => ['th' => 'ยังไม่มีผลงานนักศึกษา', 'en' => 'No student works yet'],

    // Chatbot widget
    'chatbot.title' => ['th' => 'ผู้ช่วยตอบคำถาม', 'en' => 'Site Assistant'],
    'chatbot.greeting' => ['th' => 'สวัสดีครับ พิมพ์คำถามเกี่ยวกับหลักสูตร อาจารย์ หรือข้อมูลในเว็บไซต์นี้ได้เลยครับ', 'en' => "Hi! Ask me about the program, faculty, or anything on this site."],
    'chatbot.placeholder' => ['th' => 'พิมพ์คำถาม...', 'en' => 'Type a question...'],
    'chatbot.send' => ['th' => 'ส่ง', 'en' => 'Send'],
    'chatbot.not_found' => ['th' => 'ขออภัย ไม่พบข้อมูลที่เกี่ยวข้อง ลองถามด้วยคำอื่น หรือติดต่อสาขาวิชาโดยตรง', 'en' => "Sorry, I couldn't find anything related. Try different words, or contact the program directly."],
    'chatbot.error' => ['th' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง', 'en' => 'Something went wrong. Please try again.'],
    'chatbot.read_more' => ['th' => 'ดูรายละเอียด', 'en' => 'View details'],
    'chatbot.open' => ['th' => 'เปิดแชทถามคำถาม', 'en' => 'Open chat assistant'],
    'chatbot.close' => ['th' => 'ปิด', 'en' => 'Close'],

    // Footer
    'footer.contact' => ['th' => 'ติดต่อเรา', 'en' => 'Contact Us'],
    'footer.menu' => ['th' => 'เมนู', 'en' => 'Menu'],
    'footer.rights' => ['th' => 'สงวนลิขสิทธิ์', 'en' => 'All rights reserved'],
    'footer.phone' => ['th' => 'โทร', 'en' => 'Tel'],
    'footer.email' => ['th' => 'อีเมล', 'en' => 'Email'],

    // Teachers
    'teachers.page_empty' => ['th' => 'ยังไม่มีข้อมูลอาจารย์ในระบบ', 'en' => 'No faculty information available yet'],
    'teachers.not_found' => ['th' => 'ไม่พบข้อมูลอาจารย์', 'en' => 'Faculty member not found'],
    'teachers.back_to_list' => ['th' => 'กลับไปหน้าอาจารย์', 'en' => 'Back to Faculty'],
    'teachers.back_to_list_all' => ['th' => 'กลับไปหน้าอาจารย์ทั้งหมด', 'en' => 'Back to All Faculty'],
    'teachers.phone_prefix' => ['th' => 'โทร', 'en' => 'Tel'],
    'teachers.classroom_online' => ['th' => 'Classroom Online', 'en' => 'Classroom Online'],

    // Teacher profile sections
    'profile.bio' => ['th' => 'ประวัติ', 'en' => 'Biography'],
    'profile.education' => ['th' => 'วุฒิการศึกษา', 'en' => 'Education'],
    'profile.subjects_taught' => ['th' => 'วิชาที่สอน', 'en' => 'Subjects Taught'],
    'profile.research_works' => ['th' => 'ผลงานวิจัย', 'en' => 'Research Works'],
    'profile.creative_works' => ['th' => 'ผลงานสร้างสรรค์', 'en' => 'Creative Works'],
    'profile.innovation_works' => ['th' => 'ผลงานนวัตกรรม', 'en' => 'Innovation Works'],
    'profile.competition_works' => ['th' => 'ผลงานประกวด', 'en' => 'Competition Works'],
    'profile.intellectual_property' => ['th' => 'ทรัพย์สินทางปัญญา', 'en' => 'Intellectual Property'],
    'profile.copyrights_patents' => ['th' => 'ลิขสิทธิ์/สิทธิบัตร', 'en' => 'Copyrights / Patents'],
    'profile.awards' => ['th' => 'รางวัลที่ได้รับ', 'en' => 'Awards'],

    // Course page
    'course.download_curriculum' => ['th' => 'ดาวน์โหลดเล่มหลักสูตร (PDF)', 'en' => 'Download Curriculum (PDF)'],

    // Contact page
    'contact.info_heading' => ['th' => 'ข้อมูลติดต่อ', 'en' => 'Contact Information'],
    'contact.address' => ['th' => 'ที่อยู่', 'en' => 'Address'],
    'contact.phone' => ['th' => 'โทร', 'en' => 'Tel'],
    'contact.email' => ['th' => 'อีเมล', 'en' => 'Email'],
];
