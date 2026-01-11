$(document).ready(function () {
    $('.noti-icon').on('click', function () {
        $.ajax({
            url: '../partials/mark_notifications_read.php',
            method: 'POST',
            success: function () {
                $('.noti-icon-badge').hide();
            }
        });
    });
});