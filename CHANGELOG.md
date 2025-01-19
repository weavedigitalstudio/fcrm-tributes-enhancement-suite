# Changelog

All notable changes to the FirehawkCRM Tributes Enhancement Suite will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## v1.4.0 - FH Tributes Settings Backup & Restore

- Added new Backup & Restore functionality for the FH Tributes plugin settings.
- Implemented selective settings export/import feature
- Added metadata to backup files for better tracking
- Added reset to blank functionality for individual settings
- Improved settings persistence through plugin deactivation/reactivation
- Added proper uninstall process for clean plugin removal
- Improved error handling and user feedback
- Updated documentation

## v1.3.0 - Performance Optimisations

- Completely rebuilt flower delivery disabling functionality for better performance and reliability
- Implemented new system to properly remove flower delivery features from all pages when disabled
- Optimised style loading to only enqueue CSS on tribute pages and pages containing tribute shortcodes
- Improved performance by preventing unnecessary style loading across non-tribute pages
- Optimised code to prevent unnecessary script loading and improve site performance
- Styling change for streaming and social share button colour defaults
- Minor bug fixes and tweaks to auto plugin updates

## v1.1.2

- Minor tweak to auto update code

## v1.1.1 - Auto-Update Feature Release

### What's New

- Added GitHub-based automatic updates
- Plugin updates can now be managed directly from the WordPress dashboard
- Update notifications will show release notes and version details

### Technical Details

- Implemented GitHub releases integration for version control
- Added automatic version checking against GitHub repository
- Integrated with WordPress native update system
- Added error logging for update process debugging

### Notes

- No settings changes required
- Updates will appear in your regular WordPress updates dashboard
- Requires no additional configuration

## [1.1.0] - 2024-11-23

### Added

- Initial release combining three separate plugins
- Performance and loading optimisation features
  - Conditional script/style loading
  - Flower delivery functionality (disabled by default)
  - DNS prefetch handling
  - Script cleanup
- Custom styling features
  - Colour picker with opacity support
  - Comprehensive style controls
  - Style reset functionality
- Loading animation features
  - Automatic grid detection
  - Customisable spinner
  - Smart page detection

### Changed

- Unified admin interface
- Improved page detection logic
- Enhanced asset handling

### Removed

- Individual plugin dependencies
- Legacy page ID requirements
- Unused asset files
