jQuery(document).ready(function ($) {

	// Toggle Main Plugin Enable/Disable
	$('.maiop-toggle-switch input[type="checkbox"]').on('change', function () {
		const plugin = $(this).val();
		const enabled = $(this).is(':checked');

		$.ajax({
			url: maiopAjax.ajax_url,
			type: 'POST',
			data: {
				action: 'maiop_toggle_plugin',
				plugin: plugin,
				enabled: enabled,
				nonce: maiopAjax.nonce
			},
			success: function () {
				location.reload(); // Refresh to show/hide UI
			}
		});
	});

	// Save Sitemap Subsettings in Real Time
	function saveSitemapSettings() {
		const options = {
			action: 'maiop_save_sitemap_settings',
			nonce: maiopAjax.nonce
		};

		$('.maiop-sitemap-toggle').each(function () {
			const type = $(this).data('type');
			options[`maiop_sitemap_enable_${type}`] = $(this).is(':checked') ? 1 : 0;
		});
		$('.maiop-sitemap-filename').each(function () {
			const type = $(this).data('type');
			options[`maiop_sitemap_name_${type}`] = $(this).val();
		});

		$.ajax({
			url: maiopAjax.ajax_url,
			type: 'POST',
			data: options,
			success: function (res) {
				console.log('✅ Sitemap saved:', res);
			},
			error: function (xhr) {
				console.error('❌ AJAX Error:', xhr.responseText);
			}
		});
	}

	$('.maiop-sitemap-toggle, .maiop-sitemap-filename').on('change', function () {
		const sitemapEnabled = $('input[type="checkbox"][value="sitemap"]').is(':checked');
		if (sitemapEnabled) {
			saveSitemapSettings();
		} else {
			console.log('Sitemap plugin disabled – skipping save.');
		}
	});
});
