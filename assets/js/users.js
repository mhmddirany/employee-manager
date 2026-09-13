/**
 * Required-field guard for the "Add User" form (server-side re-validates in users.php regardless).
 */
$(function () {
    $('#user-form').on('submit', function (e) {
        let valid = true;
        $(this).find('[required]').each(function () {
            const $f = $(this);
            const empty = !$f.val().trim();
            $f.toggleClass('is-invalid', empty);
            if (empty) valid = false;
        });
        if (!valid) e.preventDefault();
    });
});
