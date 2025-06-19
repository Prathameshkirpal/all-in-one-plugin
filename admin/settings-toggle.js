jQuery(document).ready(function ($) {
    $('.maiop-toggle input[type="checkbox"]').on('change', function () {
        const plugin = $(this).val();
        const enabled = $(this).is(':checked');

        $.ajax({
            type: 'POST',
            url: maiopAjax.ajax_url,
            data: {
                action: 'maiop_toggle_plugin',
                plugin: plugin,
                enabled: enabled,
                nonce: maiopAjax.nonce
            },
            success: function (res) {
                if (res.success) {
                    console.log(`Plugin "${plugin}" toggled to ${enabled}`);
                } else {
                    alert('Something went wrong!');
                }
            }
        });
    });
});
