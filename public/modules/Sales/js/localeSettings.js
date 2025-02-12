
const localeSettings = {
    ar: {
        format: 'YYYY-MM-DD',
        separator: ' إلى ',
        applyLabel: 'تطبيق',
        cancelLabel: 'إلغاء',
        fromLabel: 'من',
        toLabel: 'إلى',
        customRangeLabel: 'مخصص',
        weekLabel: 'و',
        daysOfWeek: ['أحد', 'إثنين', 'ثلاثاء', 'أربعاء', 'خميس', 'جمعة', 'سبت'],
        monthNames: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس',
            'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ],
        firstDay: 6,
        direction: 'rtl'
    },
    en: {
        format: 'YYYY-MM-DD',
        separator: ' to ',
        applyLabel: 'Apply',
        cancelLabel: 'Cancel',
        fromLabel: 'From',
        toLabel: 'To',
        customRangeLabel: 'Custom',
        weekLabel: 'W',
        daysOfWeek: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
            'September', 'October', 'November', 'December'
        ],
        firstDay: 0,
        direction: 'ltr'
    }
};

const customRanges = {
    'This Week': [moment().startOf('week'), moment().endOf('week')],
    'Last 2 Weeks': [moment().subtract(2, 'weeks').startOf('week'), moment().subtract(2, 'weeks').endOf('week')],
    'Last 3 Weeks': [moment().subtract(3, 'weeks').startOf('week'), moment().subtract(3, 'weeks').endOf('week')],
    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
    'Last 2 Months': [moment().subtract(2, 'months').startOf('month'), moment().subtract(2, 'months').endOf('month')],
    'Last 3 Months': [moment().subtract(3, 'months').startOf('month'), moment().subtract(3, 'months').endOf('month')],
    'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().subtract(6, 'months').endOf('month')],
    'This Year': [moment().startOf('year'), moment().endOf('year')],
    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
};

const arabicRanges = {
    'هذا الأسبوع': [moment().startOf('week'), moment().endOf('week')],
    'قبل أسبوعين': [moment().subtract(2, 'weeks').startOf('week'), moment().subtract(2, 'weeks').endOf('week')],
    'قبل 3 أسابيع': [moment().subtract(3, 'weeks').startOf('week'), moment().subtract(3, 'weeks').endOf('week')],
    'قبل شهر': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
    'قبل شهرين': [moment().subtract(2, 'months').startOf('month'), moment().subtract(2, 'months').endOf('month')],
    'قبل 3 شهور': [moment().subtract(3, 'months').startOf('month'), moment().subtract(3, 'months').endOf('month')],
    'قبل 6 شهور': [moment().subtract(6, 'months').startOf('month'), moment().subtract(6, 'months').endOf('month')],
    'هذه السنة': [moment().startOf('year'), moment().endOf('year')],
    'السنة الماضية': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]
};
