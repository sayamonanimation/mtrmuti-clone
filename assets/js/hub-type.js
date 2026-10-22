/**
 * Member type radios (alumni-register.php, alumni-dashboard.php): the
 * class/year fields follow the choice - "จบปีการศึกษา" for alumni,
 * "คาดว่าจะจบปีการศึกษา" for students, hidden for the general public.
 */
(function () {
    document.querySelectorAll('.hub-type').forEach(function (set) {
        var form = set.closest('form');
        var row = form && form.querySelector('.hub-class-fields');
        if (!row) return;
        var label = row.querySelector('.hub-year-label');
        function sync() {
            var picked = set.querySelector('input:checked');
            var type = picked ? picked.value : 'alumni';
            row.hidden = type === 'general';
            if (label) label.textContent = type === 'student' ? 'คาดว่าจะจบปีการศึกษา' : 'จบปีการศึกษา';
        }
        set.addEventListener('change', sync);
        sync();
    });
})();
