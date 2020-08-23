# Reusable License Key for Easy Digital Downloads Software Licensing

This plugin allows you to specify a license key that you wish to reuse endlessly for a specified EDD download.

Easy Digital Downloads Software Licensing only allows pre-specified keys or randomly generated keys to be used, but what if you want a certain (free) download to use the same key over and over again? That's not possible.

To use EDD Software Licensing's automatic update feature (e.g. with EDD GIT Updater) a license key is required. But what if it's e.g. a free download and you want to save people the hassle of having to activate it using a license key?

Using a static license key, you could programmatically activate the license, saving your customers the hassle.

## Usage

`includes/class-woosh-reusable-license.php` contains two variables: 

- `$license_key`: the license key you wish to use.
- `$item_id`: the ID of the download you wish to apply the license key on.

After setting these two variables, the specified `$license_key` will be applied with every order of `$item_id`.

Enjoy!