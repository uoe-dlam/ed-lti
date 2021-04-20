# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Fixed
- Replace imsglobal/lti with the celtic/lti package as the imsglobal package is abandoned 

## [1.3.1]
### Fixed
- Put fix in place to make sure path cannot be too big for db column (PR #35) 

## [1.3.0]
### Added
- The plugin will now use a custom notification email supplied by the upstream VLE to store as a WordPress option for student blogs (PR #33)
- The plugin will update the notification email to null if no, or an empty value, is sent. (PR #34)

## [1.2.1] - 2020-04-30
### Fixed
- Instructors can now use a browser's back button to return to the LTI student list. Previously they would have been presented with a Document Expired message (PR #22)

## [1.2.0] - 2019-06-21
### Changed
- If the VLE does not pass site category information to the blogging service, the blog will default to a course blog type (PR #21)
- Changed the `get_blog_id()` function to be called `get_blog_id_if_exists` to reduce the number of queries in the plugin by combining the `blog_exists()` and `get_blog_id` functions (PR #27, 28)

### Removed
- Remove unused function `get_blog_count()`. Also removed `blog_exists()` function from the blog handlers classes

### Fixed
- Altered register_activation_hook to point to namespaced Ed_LTI class. Previously, the activation hook was only using class name (without namespace), which resulted in errors (PR #26)

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

[Unreleased]: https://github.com/uoe-dlam/ed-lti/compare/v1.3.1...HEAD
[1.3.1]: https://github.com/uoe-dlam/ed-lti/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/uoe-dlam/ed-lti/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/uoe-dlam/ed-lti/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/uoe-dlam/ed-lti/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/uoe-dlam/ed-lti/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/uoe-dlam/ed-lti/compare/v1.0.0...v1.0.1
