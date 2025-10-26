<?php
add_action('update_option_acsp_policy','acsp_maybe_switch_to_custom',10,3);

function acsp_normalize_policy(array $a): array {
	foreach ($a as &$v) {
		$v = implode(' ', array_unique(array_filter(preg_split('/\s+/', trim($v)), 'strlen')));
	}
	ksort($a);
	return $a;
}

function acsp_maybe_switch_to_custom($old_value, $value) {
	if (!is_array($value) || !$value) return;
	$preset_key = get_option('acsp_current_preset', '');
	if (!$preset_key || 'custom' === $preset_key) return;
	$preset_pol = acsp_get_presets()[$preset_key]['policy'] ?? [];
	if (!$preset_pol) return;
	if (acsp_normalize_policy($preset_pol) !== acsp_normalize_policy($value)) {
		update_option('acsp_current_preset','custom');
	}
}
