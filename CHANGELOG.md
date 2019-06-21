# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2019-06-21

### Changed
- If the VLE does not pass site category information to the blogging service, the blog will default to a course blog type

### Fixed
- Altered register_activation_hook to point to namespaced Ed_LTI class. Previously, the activation hook was only using class name (without namespace), which resulted in errors.

## [1.1.0] - 2018-12-05

### Added
- When the first admin user is added to a blog, set site admin_email option to their email address. (PR #20)
- When user is made main admin (i.e the above) send a notification email to the user explaining as much. (PR #20)

## [1.0.1] - 2018-11-23

### Added
- Added namespaces to all classes (PR #19)

### Changed
- Removed lti prefixes from function names as no longer required (PR #19)

## [1.0.0] - 2018-10-30

- First major release


[Unreleased]: https://github.com/uoe-dlam/ed-lti/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/uoe-dlam/ed-lti/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/uoe-dlam/ed-lti/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/uoe-dlam/ed-lti/compare/v1.0.0...v1.0.1
