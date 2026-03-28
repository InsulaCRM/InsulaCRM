# Third-Party Credits and Licenses

InsulaCRM bundles or depends on the third-party software listed below.

## PHP Dependencies

| Package | Version | License | URL |
|---------|---------|---------|-----|
| laravel/framework | ^12.0 | MIT | https://laravel.com |
| laravel/tinker | ^2.10.1 | MIT | https://github.com/laravel/tinker |
| league/flysystem-aws-s3-v3 | ^3.30 | MIT | https://github.com/thephpleague/flysystem-aws-s3-v3 |
| aws/aws-sdk-php | 3.x (transitive) | Apache-2.0 | https://github.com/aws/aws-sdk-php |

## JavaScript Build Dependencies

These tools are used during development and asset compilation.

| Package | Version | License | URL |
|---------|---------|---------|-----|
| vite | ^7.0.7 | MIT | https://vitejs.dev |
| laravel-vite-plugin | ^2.0.0 | MIT | https://github.com/laravel/vite-plugin |
| tailwindcss | ^4.0.0 | MIT | https://tailwindcss.com |
| @tailwindcss/vite | ^4.0.0 | MIT | https://github.com/tailwindlabs/tailwindcss |
| axios | ^1.11.0 | MIT | https://axios-http.com |
| concurrently | ^9.0.1 | MIT | https://github.com/open-cli-tools/concurrently |

## Bundled Frontend Libraries

| Library | Version | License | URL |
|---------|---------|---------|-----|
| Tabler UI Kit | v1.0.0-beta20 | MIT | https://tabler.io |
| Tabler Icons | bundled with Tabler | MIT | https://tabler-icons.io |
| ApexCharts | v3.44.0 | MIT | https://apexcharts.com |

## Fonts

| Asset | License | URL |
|-------|---------|-----|
| Instrument Sans (served via Bunny Fonts) | SIL Open Font License 1.1 | https://fonts.bunny.net |

## Images and Branding

Application branding assets included with InsulaCRM are original works created for the product unless otherwise noted in the packaged documentation.

## License Compatibility Notes

- No GPL-only or AGPL dependencies are intentionally bundled with the application.
- Composer and npm transitive dependencies are resolved through their respective lockfiles.
- Users are responsible for complying with the licenses of any third-party services they connect to InsulaCRM, such as OpenAI, Anthropic, Google, AWS, Twilio, or other external providers.
