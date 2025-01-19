<?php
// If uninstall not called from WordPress, exit
if (!defined("WP_UNINSTALL_PLUGIN")) {
	exit();
}

// Get the option prefix used by your plugin
$option_prefix = "fcrm_enhancement_styling_";

// List of color settings to remove
$color_settings = [
	"primary-color",
	"secondary-color",
	"primary-button",
	"primary-button-text",
	"primary-button-hover",
	"primary-button-hover-text",
	"secondary-button",
	"secondary-button-text",
	"secondary-button-border",
	"secondary-button-hover",
	"secondary-button-hover-text",
	"secondary-button-hover-border",
	"focus-border-color",
	"card-background",
	"primary-shadow",
	"focus-shadow-color",
	"link-color",
];

// Remove color settings
foreach ($color_settings as $key) {
	delete_option($option_prefix . $key);
}

// Remove border radius settings
delete_option($option_prefix . "border-radius");
delete_option($option_prefix . "grid-border-radius");

// Remove module enabled status
delete_option($option_prefix . "enabled");
