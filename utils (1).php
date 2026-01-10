<?php


function translate_role($role) {
    switch ($role) {
        case 'patient': return 'مريض';
        case 'doctor': return 'طبيب';
        case 'admin': return 'مدير النظام';
        default: return 'عضو';
    }
}

function get_arabic_days() {
    return [
        0 => 'الأحد', 
        1 => 'الإثنين', 
        2 => 'الثلاثاء', 
        3 => 'الأربعاء', 
        4 => 'الخميس', 
        5 => 'الجمعة', 
        6 => 'السبت'
    ];
}
?>