# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.2] - 2024-10-09

### Fixed

- `crop` resizing mode would occasioncally use the incorect width or hight value

## [2.0.1] - 2022-07-07

### Fixed

- Jitter was occasionally using the incorrect width or height value

## [2.0.0] - 2022-07-01

### Fixed

- improved `BuildTransform()` performance ([#1](https://github.com/codewithkyle/jitter/issues/1))
- fatal edge case that could occur when a machine lacked the 3rd party libraries required to generate WEBP images
- improved `TransformImage()` performance

### Removed

- `BuildTransform()` no longer accepts the following parameters:
    - width
    - height
    - fallbackFormat

## [1.1.0] - 2022-05-31

### Added

- `croponly` mode (previously was `crop` mode)

### Fixed

- `clip` mode no longer crops or distorts the image
- `crop` mode inconsistencies -- now resizes before cropping (for old functionality see `croponly` mode)
- focal point out of bounds bug

## [1.0.0] - 2021-03-01

### Added

- public static `Jitter::BuildTransform()` method
- public static `Jitter:TransformImage()` method
- support for basic image transformations ([supported parameters](https://github.com/codewithkyle/jitter/tree/d75b3a1cc94ac018fb6b6b614e6580885331c793#using-jitter))

[Unreleased]: https://github.com/codewithkyle/jitter/compare/v2.0.1...HEAD
[2.0.1]: https://github.com/codewithkyle/jitter/releases/tag/v2.0.1
[2.0.0]: https://github.com/codewithkyle/jitter/releases/tag/v2.0.0
[1.1.0]: https://github.com/codewithkyle/jitter/releases/tag/v1.1.0
[1.0.0]: https://github.com/codewithkyle/jitter/releases/tag/v1.0.0
